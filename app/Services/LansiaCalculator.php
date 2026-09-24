<?php

namespace App\Services;

use App\Models\KunjunganRow;
use App\Models\LayananRow;
use App\Support\LansiaFields;

/**
 * LansiaCalculator — Satu-satunya sumber kebenaran perhitungan rumus turunan
 * laporan kesehatan lansia Puskesmas Payolansek.
 * Sesuai dokumentasi docs/02_DATABASE.md (§ Rumus turunan).
 */
class LansiaCalculator
{
    /**
     * Hitung seluruh kolom turunan untuk 1 baris kunjungan (1 kelurahan).
     *
     * @param array|KunjunganRow $data
     * @return array
     */
    public static function calculateKunjunganRow(array|KunjunganRow $data): array
    {
        $arr = $data instanceof KunjunganRow ? $data->toArray() : $data;

        // Helper helper pembacaan integer aman (null dianggap 0 dalam perhitungan)
        $get = fn (string $key): int => (int) ($arr[$key] ?? 0);

        // 1. total_lansia_60 = sas_a6069_l + sas_a6069_p + sas_a70_l + sas_a70_p
        $totalLansia60 = $get('sas_a6069_l')
            + $get('sas_a6069_p')
            + $get('sas_a70_l')
            + $get('sas_a70_p');

        // Total sasaran pra-lansia & lansia
        $totalSasaranAll = $get('sas_a4559_l')
            + $get('sas_a4559_p')
            + $totalLansia60;

        // 2. total_visit[A, S, T] = in_A_S_T + out_A_S_T (12 nilai)
        $totalVisit = [];
        $stdBaru = [];
        $stdAbs = [];

        foreach (LansiaFields::AGES as $age) {
            foreach (LansiaFields::GENDERS as $sex) {
                foreach (LansiaFields::VISIT_TYPES as $type) {
                    $inVal = $get("in_{$age}_{$sex}_{$type}");
                    $outVal = $get("out_{$age}_{$sex}_{$type}");
                    $totalVisit[$age][$sex][$type] = $inVal + $outVal;
                }

                // std_baru[A, S] = total_visit[A, S, baru]
                $stdBaru[$age][$sex] = $totalVisit[$age][$sex]['baru'];

                // std_abs[A, S] = total_visit[A, S, lama] + total_visit[A, S, baru]
                $stdAbs[$age][$sex] = $totalVisit[$age][$sex]['lama'] + $totalVisit[$age][$sex]['baru'];
            }
        }

        // 3. spm_l_abs = std_abs[a6069, l] + std_abs[a70, l]
        $spmLabs = $stdAbs['a6069']['l'] + $stdAbs['a70']['l'];

        // 4. spm_p_abs = std_abs[a6069, p] + std_abs[a70, p]
        $spmPabs = $stdAbs['a6069']['p'] + $stdAbs['a70']['p'];

        // 5. spm_total = spm_l_abs + spm_p_abs
        $spmTotal = $spmLabs + $spmPabs;

        // 6. spm_pct = total_lansia_60 > 0 ? round(spm_total / total_lansia_60 * 100, 2) : 0
        $spmPct = $totalLansia60 > 0
            ? round(($spmTotal / $totalLansia60) * 100, 2)
            : 0.0;

        // 7. Hitung total kunjungan dalam gedung & luar gedung
        $totalIn = 0;
        foreach (LansiaFields::kunjunganInColumns() as $col) {
            $totalIn += $get($col);
        }

        $totalOut = 0;
        foreach (LansiaFields::kunjunganOutColumns() as $col) {
            $totalOut += $get($col);
        }

        // total_kunjungan_bulan = jumlah seluruh 24 kolom kunjungan
        $totalKunjunganBulan = $totalIn + $totalOut;

        // 8. Kemandirian
        $mandiri6069Total = $get('mandiri_6069_a') + $get('mandiri_6069_b') + $get('mandiri_6069_c');
        $mandiri70Total = $get('mandiri_70_a') + $get('mandiri_70_b') + $get('mandiri_70_c');
        $mandiriTotal = $mandiri6069Total + $mandiri70Total;

        return [
            'total_lansia_60'        => $totalLansia60,
            'total_sasaran_all'      => $totalSasaranAll,
            'total_visit'            => $totalVisit,
            'std_baru'               => $stdBaru,
            'std_abs'                => $stdAbs,
            'spm_l_abs'              => $spmLabs,
            'spm_p_abs'              => $spmPabs,
            'spm_total'              => $spmTotal,
            'spm_pct'                => $spmPct,
            'total_in'               => $totalIn,
            'total_out'              => $totalOut,
            'total_kunjungan_bulan'  => $totalKunjunganBulan,
            'mandiri_6069_total'     => $mandiri6069Total,
            'mandiri_70_total'       => $mandiri70Total,
            'mandiri_total'          => $mandiriTotal,
        ];
    }

    /**
     * Hitung seluruh kolom turunan untuk 1 baris layanan (1 kelurahan).
     *
     * @param array|LayananRow $data
     * @return array
     */
    public static function calculateLayananRow(array|LayananRow $data): array
    {
        $arr = $data instanceof LayananRow ? $data->toArray() : $data;
        $get = fn (string $key): int => (int) ($arr[$key] ?? 0);

        // kelainan_total_l = Σ kel_*_l ; kelainan_total_p = Σ kel_*_p ; kelainan_total = l + p
        $kelainanTotalL = 0;
        $kelainanTotalP = 0;
        $kelainanByKey = [];

        foreach (LansiaFields::kelainanKeys() as $key) {
            $lVal = $get("kel_{$key}_l");
            $pVal = $get("kel_{$key}_p");

            $kelainanTotalL += $lVal;
            $kelainanTotalP += $pVal;
            $kelainanByKey[$key] = [
                'l'     => $lVal,
                'p'     => $pVal,
                'total' => $lVal + $pVal,
            ];
        }

        $kelainanTotal = $kelainanTotalL + $kelainanTotalP;

        // Total tindakan
        $tindakanTotal = 0;
        foreach (LansiaFields::layananTindakanColumns() as $col) {
            $tindakanTotal += $get($col);
        }

        return [
            'kelainan_total_l'  => $kelainanTotalL,
            'kelainan_total_p'  => $kelainanTotalP,
            'kelainan_total'    => $kelainanTotal,
            'kelainan_by_key'   => $kelainanByKey,
            'tindakan_total'    => $tindakanTotal,
        ];
    }

    /**
     * Hitung baris JUMLAH untuk seluruh kelurahan.
     * Sesuai aturan: persentase JUMLAH dihitung ulang dari spm_total_jumlah / total_lansia_60_jumlah * 100.
     *
     * @param array $rows Array kumpulan data kelurahan (array asosiatif raw columns)
     * @return array Hasil penjumlahan kolom input + kalkulasi turunan JUMLAH
     */
    public static function calculateSummaryRow(array $rows): array
    {
        $sum = [];

        // Inisialisasi seluruh kolom raw ke 0
        foreach (LansiaFields::allKunjunganRowColumns() as $col) {
            $sum[$col] = 0;
        }

        // Jumlahkan semua nilai input dari seluruh kelurahan
        foreach ($rows as $row) {
            $arr = $row instanceof KunjunganRow ? $row->toArray() : $row;
            foreach (LansiaFields::allKunjunganRowColumns() as $col) {
                $sum[$col] += (int) ($arr[$col] ?? 0);
            }
        }

        // Hitung turunan dari data penjumlahan (bukan merata-ratakan persentase)
        $calculations = self::calculateKunjunganRow($sum);

        return array_merge($sum, $calculations);
    }

    /**
     * Hitung baris JUMLAH untuk seluruh layanan_rows.
     *
     * @param array $rows
     * @return array
     */
    public static function calculateLayananSummaryRow(array $rows): array
    {
        $sum = [];
        foreach (LansiaFields::allLayananRowColumns() as $col) {
            if ($col === 'keterangan') {
                continue;
            }
            $sum[$col] = 0;
        }

        foreach ($rows as $row) {
            $arr = $row instanceof LayananRow ? $row->toArray() : $row;
            foreach ($sum as $col => $val) {
                $sum[$col] += (int) ($arr[$col] ?? 0);
            }
        }

        $calculations = self::calculateLayananRow($sum);

        return array_merge($sum, $calculations);
    }

    /**
     * Format angka persentase ke gaya Indonesia: 2 desimal dengan koma (mis. "23,21 %").
     */
    public static function formatPercentage(float $pct, bool $withSymbol = true): string
    {
        $formatted = number_format($pct, 2, ',', '.');
        return $withSymbol ? "{$formatted} %" : $formatted;
    }

    /**
     * Periksa potensi anomali data sebelum finalisasi (peringatan, bukan error).
     * Sesuai docs/01_SPEC.md § 5.7
     */
    public static function checkWarnings(array $kunjunganData, ?array $layananData = null): array
    {
        $warnings = [];
        $kunjCalc = self::calculateKunjunganRow($kunjunganData);

        // 1. Capaian SPM > 100%
        if ($kunjCalc['spm_pct'] > 100.0) {
            $warnings[] = "Persentase SPM ({$kunjCalc['spm_pct']}%) melebihi 100%. Periksa kembali angka kunjungan lansia >60 tahun.";
        }

        // 2. Kunjungan >60 th lebih besar dari sasaran
        if ($kunjCalc['spm_total'] > $kunjCalc['total_lansia_60'] && $kunjCalc['total_lansia_60'] > 0) {
            $warnings[] = "Total kunjungan lansia >60 th ({$kunjCalc['spm_total']}) lebih besar dari sasaran riil ({$kunjCalc['total_lansia_60']}).";
        }

        // 3. Jumlah kelainan > total kunjungan
        if ($layananData !== null) {
            $layananCalc = self::calculateLayananRow($layananData);
            if ($layananCalc['kelainan_total'] > $kunjCalc['total_kunjungan_bulan'] && $kunjCalc['total_kunjungan_bulan'] > 0) {
                $warnings[] = "Total temuan kelainan ({$layananCalc['kelainan_total']}) melebihi jumlah seluruh kunjungan bulan ini ({$kunjCalc['total_kunjungan_bulan']}).";
            }
        }

        return $warnings;
    }
}
