<?php

namespace App\Jobs;

use App\Models\Kelurahan;
use App\Models\OcrUpload;
use App\Services\Ocr\OcrProvider;
use App\Support\LansiaFields;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job ProcessOcrUpload — Memproses pembacaan gambar formulir via OcrProvider.
 * Sesuai panduan docs/06_SKILL_OCR.md.
 */
class ProcessOcrUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 90;
    public int $tries = 2;

    public function __construct(public int $ocrUploadId)
    {
    }

    public function handle(OcrProvider $provider): void
    {
        $upload = OcrUpload::find($this->ocrUploadId);
        if (! $upload || in_array($upload->status, ['done', 'applied'], true)) {
            return;
        }

        $upload->update(['status' => 'processing', 'error' => null]);

        try {
            $kelurahans = Kelurahan::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $fields = LansiaFields::sectionFields($upload->section);
            $fieldMeta = [];
            foreach ($fields as $field) {
                $fieldMeta[$field] = LansiaFields::label($field);
            }

            $schema = [
                'section'    => $upload->section,
                'fields'     => $fields,
                'field_meta' => $fieldMeta,
                'kelurahans' => $kelurahans->pluck('name')->all(),
            ];

            $result = $provider->extract($upload->image_path, $schema);

            if (! $result->isSuccess) {
                $upload->update([
                    'status' => 'failed',
                    'error'  => $result->errorMessage ?? 'Proses pembacaan formulir gagal.',
                ]);
                return;
            }

            // Normalisasi baris hasil ke kelurahan database
            $extractedRows = collect($result->rows);
            $normalizedResults = [];
            $normalizedConfidence = [];

            foreach ($kelurahans as $idx => $kel) {
                $kelNameUpper = strtoupper(trim($kel->name));

                // Cari baris yang cocok berdasarkan nama kelurahan, atau fallback ke nomor baris (1..6)
                $matchedRow = $extractedRows->first(function ($r) use ($kelNameUpper) {
                    $rName = strtoupper(trim($r['kelurahan_name'] ?? ''));
                    return $rName !== '' && (str_contains($rName, $kelNameUpper) || str_contains($kelNameUpper, $rName));
                }) ?? $extractedRows->firstWhere('row_index', $idx + 1)
                   ?? $extractedRows->get($idx);

                $rowValues = [];
                $rowConf = [];

                foreach ($fields as $field) {
                    $cellData = $matchedRow['fields'][$field] ?? null;

                    if (is_array($cellData)) {
                        $val = $cellData['value'] ?? null;
                        $conf = $cellData['confidence'] ?? 0.0;
                    } else {
                        $val = $cellData !== null ? (int) $cellData : null;
                        $conf = $val !== null ? 0.9 : 0.0;
                    }

                    $rowValues[$field] = $val;
                    $rowConf[$field] = (float) $conf;
                }

                $normalizedResults[] = [
                    'kelurahan_id'   => $kel->id,
                    'kelurahan_name' => $kel->name,
                    'row_index'      => $idx + 1,
                    'fields'         => $rowValues,
                ];

                $normalizedConfidence[] = [
                    'kelurahan_id'   => $kel->id,
                    'kelurahan_name' => $kel->name,
                    'row_index'      => $idx + 1,
                    'fields'         => $rowConf,
                ];
            }

            $upload->update([
                'status'          => 'done',
                'result_json'     => $normalizedResults,
                'confidence_json' => $normalizedConfidence,
                'error'           => null,
            ]);
        } catch (Exception $e) {
            Log::error("ProcessOcrUpload exception for upload #{$this->ocrUploadId}: " . $e->getMessage());
            $upload->update([
                'status' => 'failed',
                'error'  => 'Terjadi kesalahan sistem saat memproses foto: ' . $e->getMessage(),
            ]);
        }
    }
}
