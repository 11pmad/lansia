<?php

namespace App\Services;

use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Support\LansiaFields;
use Carbon\Carbon;
use Exception;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * ReportExporter — Layanan ekspor laporan ke format Excel baku Dinkes TA 2026.
 * Sesuai dokumentasi docs/03_EXCEL_MAPPING.md.
 */
class ReportExporter
{
    protected string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?? storage_path('app/templates/laporan_lansia_template.xlsx');
    }

    /**
     * Ekspor laporan tahunan (atau bulan tertentu) ke file Excel baru.
     *
     * @param int $year
     * @param int|null $month Bulan 1..12 (null untuk 1 tahun penuh)
     * @return array ['path' => string, 'filename' => string]
     * @throws Exception
     */
    public function export(int $year, ?int $month = null): array
    {
        if (! file_exists($this->templatePath)) {
            throw new Exception("Berkas template Excel tidak ditemukan pada path: {$this->templatePath}");
        }

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($this->templatePath);

        // Ambil kedua sheet utama via pencocokan trimmed name
        $sheetKunjungan = $this->getSheetByTrimmedName($spreadsheet, 'Lap. Kunjungan');
        $sheetLayanan = $this->getSheetByTrimmedName($spreadsheet, 'Lap. Layanan Lansia');

        if (! $sheetKunjungan || ! $sheetLayanan) {
            throw new Exception("Sheet 'Lap. Kunjungan' atau 'Lap. Layanan Lansia' tidak ditemukan dalam template.");
        }

        // Hapus Sheet1 (catatan coretan pada template asli)
        $sheet1 = $this->getSheetByTrimmedName($spreadsheet, 'Sheet1');
        if ($sheet1) {
            $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($sheet1));
        }

        $puskesmas = Puskesmas::first();
        $puskesmasName = strtoupper($puskesmas?->name ?? 'PUSKESMAS PAYOLANSEK');
        $cityName = strtoupper($puskesmas?->city ?? 'KOTA PAYAKUMBUH');

        $kelurahans = Kelurahan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Ambil laporan dari database untuk tahun terkait
        $query = MonthlyReport::where('year', $year)
            ->with(['kunjunganRows', 'layananRows']);

        if ($month !== null) {
            $query->where('month', $month);
        }

        $reports = $query->get()->keyBy('month');

        // Rentang bulan yang diproses
        $monthsToProcess = range(1, 12);

        foreach ($monthsToProcess as $m) {
            $offset = 22 * ($m - 1);
            $monthInfo = StatistikService::MONTH_NAMES[$m];
            $monthUpper = strtoupper($monthInfo['full']);
            $report = $reports->get($m);

            // 1. Header Tahun Dinamis di baris 1 (Fix #2)
            $rowTitle = 1 + $offset;
            $this->updateTitleYear($sheetKunjungan, $rowTitle, $year);
            $this->updateTitleYear($sheetLayanan, $rowTitle, $year);

            // Baris 2 & 3: Nama Puskesmas dan Kota
            $rowPusk = 2 + $offset;
            $rowCity = 3 + $offset;
            $sheetKunjungan->setCellValue("A{$rowPusk}", $puskesmasName);
            $sheetLayanan->setCellValue("A{$rowPusk}", $puskesmasName);
            $sheetKunjungan->setCellValue("A{$rowCity}", $cityName);
            $sheetLayanan->setCellValue("A{$rowCity}", $cityName);

            // 2. Baris Bulan (Fix #1: memastikan Agustus dan bulan lainnya tertulis : NAMA_BULAN)
            $rowMonth = 5 + $offset;
            $sheetKunjungan->setCellValue("C{$rowMonth}", ": {$monthUpper}");
            $sheetLayanan->setCellValue("C{$rowMonth}", ": {$monthUpper}");

            // 3. Kelurahan 1..6 (Fix #3: nomor urut 1..6, kelurahan ke-6 TALANG bernomor 6)
            foreach ($kelurahans as $idx => $kel) {
                $i = $idx + 1; // 1-based index
                $r = 12 + $offset + ($i - 1);

                $sheetKunjungan->setCellValue("A{$r}", $i);
                $sheetKunjungan->setCellValue("B{$r}", $kel->name);
                $sheetLayanan->setCellValue("A{$r}", $i);
                $sheetLayanan->setCellValue("B{$r}", $kel->name);

                // Fix #4: Pastikan rumus AM Sheet Layanan benar untuk tiap baris (penjumlahan kolom P)
                $sheetLayanan->setCellValue(
                    "AM{$r}",
                    "=K{$r}+M{$r}+O{$r}+Q{$r}+S{$r}+U{$r}+W{$r}+Y{$r}+AA{$r}+AC{$r}+AE{$r}+AG{$r}+AI{$r}+AK{$r}"
                );
            }

            // Jika ada data laporan untuk bulan ini, isi seluruh sel input
            if ($report && ($month === null || $month === $m)) {
                $this->populateMonthData(
                    $sheetKunjungan,
                    $sheetLayanan,
                    $report,
                    $kelurahans,
                    $offset,
                    $m
                );
            } else {
                // Bersihkan footer statis jika bulan kosong
                $rowFooter = 21 + $offset;
                $sheetKunjungan->setCellValue("BD{$rowFooter}", '');
                $sheetKunjungan->setCellValue("BN{$rowFooter}", '');
            }
        }

        // Simpan ke direktori ekspor
        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        if ($month !== null) {
            $monthName = strtoupper(StatistikService::MONTH_NAMES[$month]['full']);
            $filename = "Laporan_Lansia_Payakumbuh_{$year}_{$monthName}.xlsx";
        } else {
            $filename = "Laporan_Lansia_Payakumbuh_{$year}_SEMUA.xlsx";
        }

        $outputPath = $exportDir . '/' . uniqid('export_') . '_' . $filename;

        $writer = new XlsxWriter($spreadsheet);
        // setPreCalculateFormulas(false) agar Excel menghitung rumus asli saat berkas dibuka pengguna
        $writer->setPreCalculateFormulas(false);
        $writer->save($outputPath);

        return [
            'path'     => $outputPath,
            'filename' => $filename,
        ];
    }

    /**
     * Isi seluruh data baris kelurahan pada Sheet Kunjungan dan Sheet Layanan.
     */
    protected function populateMonthData(
        Worksheet $sheetK,
        Worksheet $sheetL,
        MonthlyReport $report,
        $kelurahans,
        int $offset,
        int $month
    ): void {
        $kunjunganRows = $report->kunjunganRows->keyBy('kelurahan_id');
        $layananRows = $report->layananRows->keyBy('kelurahan_id');
        $nowFormatted = 'Payakumbuh, ' . Carbon::now()->translatedFormat('d F Y');

        $totalKunjunganBulan = 0;

        foreach ($kelurahans as $idx => $kel) {
            $i = $idx + 1;
            $r = 12 + $offset + ($i - 1);

            $kRow = $kunjunganRows->get($kel->id);
            $lRow = $layananRows->get($kel->id);

            // ==========================================
            // A. PENGISIAN SHEET KUNJUNGAN
            // ==========================================
            if ($kRow) {
                // Kolom C..G (Umum)
                $this->writeCell($sheetK, 3, $r, $kRow->posyandu_count);
                $this->writeCell($sheetK, 4, $r, $kRow->pemberdayaan_count);
                $this->writeCell($sheetK, 5, $r, $kRow->posyandu_ptm_count);
                $this->writeCell($sheetK, 6, $r, $kRow->kader_count);
                $this->writeCell($sheetK, 7, $r, $kRow->kader_trained_count);

                // Kolom H..M (Sasaran)
                $this->writeCell($sheetK, 8, $r, $kRow->sas_a4559_l);
                $this->writeCell($sheetK, 9, $r, $kRow->sas_a4559_p);
                $this->writeCell($sheetK, 10, $r, $kRow->sas_a6069_l);
                $this->writeCell($sheetK, 11, $r, $kRow->sas_a6069_p);
                $this->writeCell($sheetK, 12, $r, $kRow->sas_a70_l);
                $this->writeCell($sheetK, 13, $r, $kRow->sas_a70_p);

                // Kolom O (JKN) — Kolom N adalah rumus total sasaran, JANGAN DITULIS
                $this->writeCell($sheetK, 15, $r, $kRow->jkn_count);

                // 12 Kolom Kunjungan Dalam Gedung (P..AA, cols 16..27)
                // Urutan: idx = umur_i * 4 + sex_i * 2 + tipe_i
                foreach (LansiaFields::AGES as $ageIdx => $age) {
                    foreach (LansiaFields::GENDERS as $sexIdx => $sex) {
                        foreach (LansiaFields::VISIT_TYPES as $typeIdx => $type) {
                            $idxCol = ($ageIdx * 4) + ($sexIdx * 2) + $typeIdx;
                            $inCol = 16 + $idxCol;
                            $field = "in_{$age}_{$sex}_{$type}";
                            $this->writeCell($sheetK, $inCol, $r, $kRow->{$field});

                            if ($kRow->{$field} !== null) {
                                $totalKunjunganBulan += (int) $kRow->{$field};
                            }
                        }
                    }
                }

                // 12 Kolom Kunjungan Luar Gedung (AB..AM, cols 28..39)
                foreach (LansiaFields::AGES as $ageIdx => $age) {
                    foreach (LansiaFields::GENDERS as $sexIdx => $sex) {
                        foreach (LansiaFields::VISIT_TYPES as $typeIdx => $type) {
                            $idxCol = ($ageIdx * 4) + ($sexIdx * 2) + $typeIdx;
                            $outCol = 28 + $idxCol;
                            $field = "out_{$age}_{$sex}_{$type}";
                            $this->writeCell($sheetK, $outCol, $r, $kRow->{$field});

                            if ($kRow->{$field} !== null) {
                                $totalKunjunganBulan += (int) $kRow->{$field};
                            }
                        }
                    }
                }

                // Kolom Mandiri (BP..BU, cols 68..73)
                $this->writeCell($sheetK, 68, $r, $kRow->mandiri_6069_a);
                $this->writeCell($sheetK, 69, $r, $kRow->mandiri_6069_b);
                $this->writeCell($sheetK, 70, $r, $kRow->mandiri_6069_c);
                $this->writeCell($sheetK, 71, $r, $kRow->mandiri_70_a);
                $this->writeCell($sheetK, 72, $r, $kRow->mandiri_70_b);
                $this->writeCell($sheetK, 73, $r, $kRow->mandiri_70_c);
            }

            // ==========================================
            // B. PENGISIAN SHEET LAYANAN LANSIA
            // ==========================================
            if ($lRow || $kRow) {
                // Kolom C..H (Sasaran: salin dari kunjungan jika layanan belum diisi)
                $this->writeCell($sheetL, 3, $r, $lRow?->sas_a4559_l ?? $kRow?->sas_a4559_l);
                $this->writeCell($sheetL, 4, $r, $lRow?->sas_a4559_p ?? $kRow?->sas_a4559_p);
                $this->writeCell($sheetL, 5, $r, $lRow?->sas_a6069_l ?? $kRow?->sas_a6069_l);
                $this->writeCell($sheetL, 6, $r, $lRow?->sas_a6069_p ?? $kRow?->sas_a6069_p);
                $this->writeCell($sheetL, 7, $r, $lRow?->sas_a70_l ?? $kRow?->sas_a70_l);
                $this->writeCell($sheetL, 8, $r, $lRow?->sas_a70_p ?? $kRow?->sas_a70_p);
                // Kolom I adalah rumus total sasaran, JANGAN DITULIS

                if ($lRow) {
                    // 28 Kolom Kelainan (J..AK, cols 10..37)
                    // Col = 10 + key_i * 2 + sex_i
                    foreach (LansiaFields::kelainanKeys() as $keyIdx => $key) {
                        foreach (LansiaFields::GENDERS as $sexIdx => $sex) {
                            $kelCol = 10 + ($keyIdx * 2) + $sexIdx;
                            $field = "kel_{$key}_{$sex}";
                            $this->writeCell($sheetL, $kelCol, $r, $lRow->{$field});
                        }
                    }

                    // Tindakan & Lain-lain (AO..AY, cols 41..51) — AL, AM, AN adalah rumus
                    $this->writeCell($sheetL, 41, $r, $lRow->pengobatan_edukasi);
                    $this->writeCell($sheetL, 42, $r, $lRow->pengobatan_obati);
                    $this->writeCell($sheetL, 43, $r, $lRow->pengobatan_rujuk);
                    $this->writeCell($sheetL, 44, $r, $lRow->konseling_baru);
                    $this->writeCell($sheetL, 45, $r, $lRow->konseling_lama);
                    $this->writeCell($sheetL, 46, $r, $lRow->konseling_selesai);
                    $this->writeCell($sheetL, 47, $r, $lRow->penyuluhan);
                    $this->writeCell($sheetL, 48, $r, $lRow->lansia_bekerja);
                    $this->writeCell($sheetL, 49, $r, $lRow->panti_dibina);
                    $this->writeCell($sheetL, 50, $r, $lRow->homecare);
                    $this->writeCell($sheetL, 51, $r, $lRow->keterangan, false);
                }
            }
        }

        // Fix #5: Update Teks Bebas & Tanggal Footer
        $rowFooter = 21 + $offset;
        $sheetK->setCellValue("BB{$rowFooter}", 'kunj bln ini :');
        $sheetK->setCellValue("BD{$rowFooter}", "=AN" . (18 + $offset));
        $sheetK->setCellValue("BN{$rowFooter}", $nowFormatted);

        // Footer tanggal pada Sheet Layanan (kolom AT / AU baris footer)
        $sheetL->setCellValue("AT{$rowFooter}", $nowFormatted);
    }

    /**
     * Tulis sel jika nilai tidak null (null dibiarkan kosong, bukan 0).
     */
    protected function writeCell(Worksheet $sheet, int $col, int $row, mixed $val, bool $isNumeric = true): void
    {
        if ($val === null || $val === '') {
            return;
        }

        $coord = Coordinate::stringFromColumnIndex($col) . $row;

        if ($isNumeric && is_numeric($val)) {
            $sheet->setCellValueExplicit($coord, (int) $val, DataType::TYPE_NUMERIC);
        } else {
            $sheet->setCellValue($coord, (string) $val);
        }
    }

    /**
     * Ganti "TA. 20xx" dengan tahun yang diminta pada header sheet.
     */
    protected function updateTitleYear(Worksheet $sheet, int $row, int $year): void
    {
        $coord = "A{$row}";
        $val = $sheet->getCell($coord)->getValue();
        if ($val && is_string($val)) {
            $newVal = preg_replace('/TA\.\s*\d{4}/i', "TA. {$year}", $val);
            if ($newVal !== $val) {
                $sheet->setCellValue($coord, $newVal);
            }
        }
    }

    /**
     * Cari sheet berdasarkan nama yang di-trim whitespace-nya.
     */
    protected function getSheetByTrimmedName(Spreadsheet $spreadsheet, string $targetName): ?Worksheet
    {
        $target = trim($targetName);
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            if (trim($sheet->getTitle()) === $target) {
                return $sheet;
            }
        }
        return null;
    }
}
