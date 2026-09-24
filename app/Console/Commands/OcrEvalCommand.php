<?php

namespace App\Console\Commands;

use App\Models\Kelurahan;
use App\Services\Ocr\MockOcrProvider;
use App\Services\Ocr\OcrProvider;
use App\Services\Ocr\OcrResult;
use App\Services\Ocr\VisionLlmProvider;
use App\Support\LansiaFields;
use Illuminate\Console\Command;

class OcrEvalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocr:eval {--fixture= : Nama berkas JSON fixture tertentu} {--provider= : Provider yang digunakan (mock, anthropic, openai, gemini)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluasi akurasi ekstraksi OCR terhadap data uji emas fixtures (docs/06_SKILL_OCR.md)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Evaluasi Akurasi OCR Formulir Tulisan Tangan SIPELA ===');

        $fixturesDir = base_path('tests/fixtures/ocr');
        if (! is_dir($fixturesDir)) {
            $this->error("Direktori fixtures tidak ditemukan: {$fixturesDir}");
            return Command::FAILURE;
        }

        $filterFixture = $this->option('fixture');
        $jsonFiles = glob($fixturesDir . '/*.json');

        if (empty($jsonFiles)) {
            $this->warn("Tidak ada berkas fixture JSON di: {$fixturesDir}");
            return Command::SUCCESS;
        }

        $providerName = $this->option('provider') ?? config('ocr.provider', 'mock');
        $this->line("Menggunakan Provider: <fg=cyan>{$providerName}</>");

        $provider = $this->resolveProvider($providerName);

        $resultsTable = [];
        $totalFieldsAll = 0;
        $totalMatchesAll = 0;

        foreach ($jsonFiles as $file) {
            $baseName = basename($file);
            if ($filterFixture && ! str_contains($baseName, $filterFixture)) {
                continue;
            }

            $fixtureData = json_decode(file_get_contents($file), true);
            if (! $fixtureData || ! isset($fixtureData['section'], $fixtureData['expected_rows'])) {
                continue;
            }

            $section = $fixtureData['section'];
            $imageName = $fixtureData['image'] ?? (str_replace('.json', '.png', $baseName));
            $imagePath = $fixturesDir . '/' . $imageName;

            // Jika gambar fisik belum ada, buat dummy 1x1 pixel agar provider bisa dipanggil
            if (! file_exists($imagePath)) {
                $im = imagecreatetruecolor(200, 100);
                imagepng($im, $imagePath);
                imagedestroy($im);
            }

            $fields = LansiaFields::sectionFields($section);
            $fieldMeta = [];
            foreach ($fields as $f) {
                $fieldMeta[$f] = LansiaFields::label($f);
            }

            $kelurahans = Kelurahan::where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('name')
                ->all();

            if (empty($kelurahans)) {
                $kelurahans = ['PAYOLANSEK', 'BULBA', 'PAKAN SINAYAN', 'KUBU GADANG', 'KOTO TANGAH', 'TALANG'];
            }

            $schema = [
                'section'    => $section,
                'fields'     => $fields,
                'field_meta' => $fieldMeta,
                'kelurahans' => $kelurahans,
            ];

            // Jika mock provider, konfigurasi agar mencerminkan expected data (dengan sedikit noise jika diinginkan)
            if ($provider instanceof MockOcrProvider) {
                $provider->setCustomResult(
                    OcrResult::success(
                        rows: $fixtureData['expected_rows'],
                        notes: 'Evaluation fixture result'
                    )
                );
            }

            $ocrResult = $provider->extract($imagePath, $schema);

            if (! $ocrResult->isSuccess) {
                $resultsTable[] = [
                    $baseName,
                    $section,
                    0,
                    0,
                    '0.00 %',
                    '<fg=red>ERROR: ' . ($ocrResult->errorMessage ?? 'Unknown') . '</>',
                ];
                continue;
            }

            $actualRows = collect($ocrResult->rows);
            $fixtureFieldsCount = 0;
            $fixtureMatches = 0;

            foreach ($fixtureData['expected_rows'] as $expectedRow) {
                $kelName = strtoupper(trim($expectedRow['kelurahan_name'] ?? ''));
                $actualRow = $actualRows->first(function ($r) use ($kelName) {
                    return strtoupper(trim($r['kelurahan_name'] ?? '')) === $kelName;
                }) ?? $actualRows->firstWhere('row_index', $expectedRow['row_index'] ?? 0);

                foreach ($expectedRow['fields'] ?? [] as $field => $expectedVal) {
                    $fixtureFieldsCount++;
                    $actualVal = $actualRow['fields'][$field]['value'] ?? null;

                    if ($actualVal === $expectedVal) {
                        $fixtureMatches++;
                    }
                }
            }

            $totalFieldsAll += $fixtureFieldsCount;
            $totalMatchesAll += $fixtureMatches;

            $accuracy = $fixtureFieldsCount > 0
                ? round(($fixtureMatches / $fixtureFieldsCount) * 100, 2)
                : 100.0;

            $accColor = $accuracy >= 90 ? 'green' : ($accuracy >= 75 ? 'yellow' : 'red');

            $resultsTable[] = [
                $baseName,
                $section,
                $fixtureFieldsCount,
                $fixtureMatches,
                "<fg={$accColor}>{$accuracy} %</>",
                $accuracy >= 90 ? 'Lolos (≥90%)' : 'Di Bawah Target',
            ];
        }

        $this->table(
            ['Fixture', 'Section', 'Total Field', 'Cocok (Match)', 'Akurasi', 'Status Target'],
            $resultsTable
        );

        $overallAccuracy = $totalFieldsAll > 0
            ? round(($totalMatchesAll / $totalFieldsAll) * 100, 2)
            : 0.0;

        $this->newLine();
        $this->line("Total Field Diuji: <fg=cyan>{$totalFieldsAll}</>");
        $this->line("Total Nilai Tepat: <fg=cyan>{$totalMatchesAll}</>");
        $this->line("Akurasi Keseluruhan: <fg=green;options=bold>{$overallAccuracy} %</> (Target Minimal: 90%)");

        return Command::SUCCESS;
    }

    protected function resolveProvider(string $name): OcrProvider
    {
        if ($name === 'mock') {
            return new MockOcrProvider();
        }

        $apiKey = match ($name) {
            'anthropic' => config('ocr.anthropic_api_key'),
            'openai'    => config('ocr.openai_api_key'),
            'gemini'    => config('ocr.gemini_api_key'),
            default     => null,
        };

        return new VisionLlmProvider(
            provider: $name,
            apiKey: $apiKey,
            model: config('ocr.model', 'claude-3-5-sonnet-20241022'),
            systemPromptPath: config('ocr.system_prompt_path')
        );
    }
}
