<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessOcrUpload;
use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\LayananRow;
use App\Models\MonthlyReport;
use App\Models\OcrUpload;
use App\Support\LansiaFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OcrController extends Controller
{
    public const SECTIONS = [
        [
            'id'        => 'kunjungan_umum_sasaran',
            'title'     => 'Kunjungan: Umum & Sasaran',
            'desc'      => 'Posyandu, kader, sasaran umur (45-59, 60-69, >70), dan kepemilikan JKN.',
            'form_type' => 'kunjungan',
        ],
        [
            'id'        => 'kunjungan_dalam',
            'title'     => 'Kunjungan: Dalam Gedung (Puskesmas)',
            'desc'      => '12 kolom kunjungan lansia dalam gedung menurut umur, jenis kelamin, dan lama/baru.',
            'form_type' => 'kunjungan',
        ],
        [
            'id'        => 'kunjungan_luar',
            'title'     => 'Kunjungan: Luar Gedung (Posyandu)',
            'desc'      => '12 kolom kunjungan lansia luar gedung menurut umur, jenis kelamin, dan lama/baru.',
            'form_type' => 'kunjungan',
        ],
        [
            'id'        => 'kunjungan_mandiri',
            'title'     => 'Kunjungan: Tingkat Kemandirian',
            'desc'      => '6 kolom tingkat kemandirian kategori A (mandiri), B (ringan/sedang), dan C (berat).',
            'form_type' => 'kunjungan',
        ],
        [
            'id'        => 'layanan_sasaran_kelainan',
            'title'     => 'Layanan: Sasaran & 14 Kelainan',
            'desc'      => 'Sasaran lansia dan 28 kolom jenis kelainan kesehatan lansia (Laki-laki & Perempuan).',
            'form_type' => 'layanan',
        ],
        [
            'id'        => 'layanan_tindakan',
            'title'     => 'Layanan: Tindakan & Lain-lain',
            'desc'      => 'Tindakan pengobatan, konseling, penyuluhan, lansia bekerja, panti, dan homecare.',
            'form_type' => 'layanan',
        ],
    ];

    /**
     * Halaman /scan: Pilih bagian formulir dan unggah / potret foto.
     */
    public function create(Request $request): Response
    {
        $draftReports = MonthlyReport::where('status', 'draft')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get(['id', 'year', 'month', 'status']);

        $selectedReportId = (int) $request->input('report_id', $draftReports->first()?->id ?? 0);
        $selectedSection = $request->input('section', self::SECTIONS[0]['id']);

        $dailyLimit = config('ocr.daily_limit', 30);
        $todayUsage = OcrUpload::where('created_by', auth()->id())
            ->whereDate('created_at', today())
            ->count();

        return Inertia::render('Ocr/Scan', [
            'draftReports'      => $draftReports,
            'selectedReportId'  => $selectedReportId,
            'selectedSection'   => $selectedSection,
            'sections'          => self::SECTIONS,
            'todayUsage'        => $todayUsage,
            'dailyLimit'        => $dailyLimit,
            'ocrProvider'       => config('ocr.provider', 'mock'),
        ]);
    }

    /**
     * Terima upload foto formulir dan jalankan job OCR.
     */
    public function store(Request $request): RedirectResponse
    {
        $validSections = array_column(self::SECTIONS, 'id');

        $validated = $request->validate([
            'monthly_report_id' => 'required|exists:monthly_reports,id',
            'section'           => 'required|in:' . implode(',', $validSections),
            'image'             => 'required|image|mimes:jpeg,jpg,png,webp|max:8192',
        ], [
            'monthly_report_id.required' => 'Pilih bulan laporan draft terlebih dahulu.',
            'section.required'           => 'Pilih bagian formulir yang dipindai.',
            'image.required'             => 'Silakan ambil foto atau unggah berkas formulir.',
            'image.image'                => 'Berkas harus berupa gambar (JPG, PNG, atau WEBP).',
            'image.max'                  => 'Ukuran foto maksimal 8 MB.',
        ]);

        $report = MonthlyReport::findOrFail($validated['monthly_report_id']);
        if ($report->isFinal()) {
            return back()->withErrors(['monthly_report_id' => 'Laporan bulan ini telah berstatus Final (terkunci). Buka kunci terlebih dahulu jika ingin memperbarui.']);
        }

        // Batas scan harian
        $dailyLimit = config('ocr.daily_limit', 30);
        $todayCount = OcrUpload::where('created_by', auth()->id())
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $dailyLimit) {
            return back()->withErrors([
                'image' => "Batas pemindaian harian Anda ({$dailyLimit} kali per hari) telah tercapai. Silakan lanjutkan pengisian formulir secara manual.",
            ]);
        }

        // Simpan gambar secara privat
        $relPath = $request->file('image')->store('ocr', 'local');
        $absolutePath = Storage::disk('local')->path($relPath);

        $ocrUpload = OcrUpload::create([
            'monthly_report_id' => $report->id,
            'section'           => $validated['section'],
            'image_path'        => $absolutePath,
            'status'            => 'queued',
            'created_by'        => auth()->id(),
        ]);

        // Jalankan Job OCR
        ProcessOcrUpload::dispatch($ocrUpload->id);

        return redirect()->route('scan.show', $ocrUpload->id);
    }

    /**
     * Halaman /scan/{id}: Pratinjau foto dan formulir hasil pembacaan.
     */
    public function show(OcrUpload $ocrUpload): Response
    {
        $ocrUpload->load(['monthlyReport', 'creator']);

        $sectionMeta = collect(self::SECTIONS)->firstWhere('id', $ocrUpload->section);
        $fields = LansiaFields::sectionFields($ocrUpload->section);

        $fieldDefs = [];
        foreach ($fields as $f) {
            $fieldDefs[] = [
                'name'  => $f,
                'label' => LansiaFields::label($f),
            ];
        }

        $kelurahans = Kelurahan::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'sort_order']);

        // Data saat ini di DB untuk referensi perbandingan
        $report = $ocrUpload->monthlyReport;
        $currentRows = [];

        if ($report) {
            if ($sectionMeta['form_type'] === 'kunjungan') {
                $currentRows = $report->kunjunganRows()->get()->keyBy('kelurahan_id');
            } else {
                $currentRows = $report->layananRows()->get()->keyBy('kelurahan_id');
            }
        }

        return Inertia::render('Ocr/Preview', [
            'upload'      => [
                'id'              => $ocrUpload->id,
                'section'         => $ocrUpload->section,
                'section_title'   => $sectionMeta['title'] ?? $ocrUpload->section,
                'form_type'       => $sectionMeta['form_type'] ?? 'kunjungan',
                'status'          => $ocrUpload->status,
                'error'           => $ocrUpload->error,
                'result_json'     => $ocrUpload->result_json,
                'confidence_json' => $ocrUpload->confidence_json,
                'created_at'      => $ocrUpload->created_at->format('d/m/Y H:i'),
            ],
            'report'      => [
                'id'     => $report?->id,
                'year'   => $report?->year,
                'month'  => $report?->month,
                'status' => $report?->status,
            ],
            'fields'      => $fieldDefs,
            'kelurahans'  => $kelurahans,
            'currentRows' => $currentRows,
            'imageUrl'    => route('scan.image', $ocrUpload->id),
        ]);
    }

    /**
     * Tampilkan berkas foto yang tersimpan di disk privat dengan otorisasi aman.
     */
    public function image(OcrUpload $ocrUpload): BinaryFileResponse
    {
        if (! file_exists($ocrUpload->image_path)) {
            abort(404, 'Berkas foto tidak ditemukan di server.');
        }

        return response()->file($ocrUpload->image_path);
    }

    /**
     * Endpoint polling status proses OCR oleh frontend.
     */
    public function status(OcrUpload $ocrUpload): JsonResponse
    {
        return response()->json([
            'id'              => $ocrUpload->id,
            'status'          => $ocrUpload->status,
            'error'           => $ocrUpload->error,
            'result_json'     => $ocrUpload->result_json,
            'confidence_json' => $ocrUpload->confidence_json,
        ]);
    }

    /**
     * Simpan data hasil OCR/koreksi petugas ke tabel kunjungan_rows atau layanan_rows.
     */
    public function apply(Request $request, OcrUpload $ocrUpload): RedirectResponse
    {
        $report = $ocrUpload->monthlyReport;
        if (! $report || $report->isFinal()) {
            return back()->withErrors(['general' => 'Laporan tidak ditemukan atau sudah berstatus Final.']);
        }

        $sectionMeta = collect(self::SECTIONS)->firstWhere('id', $ocrUpload->section);
        $isKunjungan = ($sectionMeta['form_type'] ?? 'kunjungan') === 'kunjungan';
        $allowedFields = LansiaFields::sectionFields($ocrUpload->section);

        $validated = $request->validate([
            'rows'                     => 'required|array',
            'rows.*.kelurahan_id'      => 'required|exists:kelurahans,id',
            'rows.*.fields'            => 'required|array',
        ]);

        foreach ($validated['rows'] as $rowData) {
            $kelId = (int) $rowData['kelurahan_id'];
            $submittedFields = $rowData['fields'] ?? [];

            // Filter hanya field yang ada di skema bagian ini
            $dataToSave = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $submittedFields)) {
                    $val = $submittedFields[$field];
                    $dataToSave[$field] = ($val !== null && $val !== '') ? (int) $val : null;
                }
            }

            if (! empty($dataToSave)) {
                if ($isKunjungan) {
                    KunjunganRow::updateOrCreate(
                        ['monthly_report_id' => $report->id, 'kelurahan_id' => $kelId],
                        $dataToSave
                    );
                } else {
                    LayananRow::updateOrCreate(
                        ['monthly_report_id' => $report->id, 'kelurahan_id' => $kelId],
                        $dataToSave
                    );
                }
            }
        }

        $ocrUpload->update([
            'status'     => 'applied',
            'applied_at' => now(),
        ]);

        // Redirect ke form terkait dengan pesan sukses
        $redirectRoute = $isKunjungan
            ? route('forms.kunjungan.edit', $report->id)
            : route('forms.layanan.edit', $report->id);

        return redirect($redirectRoute)->with(
            'success',
            "Hasil scan bagian '{$sectionMeta['title']}' berhasil disimpan ke draf laporan."
        );
    }
}
