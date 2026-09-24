<?php

namespace Tests\Unit;

use App\Services\LansiaCalculator;
use PHPUnit\Framework\TestCase;

class LansiaCalculatorTest extends TestCase
{
    /**
     * Uji Emas (Golden Test) sesuai docs/02_DATABASE.md
     * Kelurahan PAYOLANSEK, Januari 2026 (dari data resmi Excel):
     * - Sasaran: 45-59 L/P = 200/243; 60-69 = 180/209; >70 = 110/117 -> total_lansia_60 = 616
     * - Kunjungan baru dalam gedung: 45-59 L 20, P 50; 60-69 L 16, P 40; >70 L 29, P 26
     * - Kunjungan baru luar gedung: 45-59 L 5, P 10; 60-69 L 14, P 16; >70 L 0, P 2
     * - Semua "lama" = 0
     * Hasil wajib:
     * - spm_l_abs = 59
     * - spm_p_abs = 84
     * - spm_total = 143
     * - spm_pct   = 23.21
     */
    public function test_golden_test_payolansek_januari_2026(): void
    {
        $input = [
            // Sasaran
            'sas_a4559_l' => 200,
            'sas_a4559_p' => 243,
            'sas_a6069_l' => 180,
            'sas_a6069_p' => 209,
            'sas_a70_l'   => 110,
            'sas_a70_p'   => 117,

            // Kunjungan dalam gedung (baru)
            'in_a4559_l_baru' => 20,
            'in_a4559_p_baru' => 50,
            'in_a6069_l_baru' => 16,
            'in_a6069_p_baru' => 40,
            'in_a70_l_baru'   => 29,
            'in_a70_p_baru'   => 26,

            // Kunjungan luar gedung (baru)
            'out_a4559_l_baru' => 5,
            'out_a4559_p_baru' => 10,
            'out_a6069_l_baru' => 14,
            'out_a6069_p_baru' => 16,
            'out_a70_l_baru'   => 0,
            'out_a70_p_baru'   => 2,

            // Semua kunjungan "lama" bernilai 0 atau null
            'in_a4559_l_lama' => 0,
            'in_a4559_p_lama' => 0,
            'in_a6069_l_lama' => 0,
            'in_a6069_p_lama' => 0,
            'in_a70_l_lama'   => 0,
            'in_a70_p_lama'   => 0,
            'out_a4559_l_lama' => 0,
            'out_a4559_p_lama' => 0,
            'out_a6069_l_lama' => 0,
            'out_a6069_p_lama' => 0,
            'out_a70_l_lama'   => 0,
            'out_a70_p_lama'   => 0,
        ];

        $result = LansiaCalculator::calculateKunjunganRow($input);

        // 1. Total lansia >60 th = 180 + 209 + 110 + 117 = 616
        $this->assertSame(616, $result['total_lansia_60']);

        // 2. spm_l_abs = (16+14) + (29+0) = 30 + 29 = 59
        $this->assertSame(59, $result['spm_l_abs']);

        // 3. spm_p_abs = (40+16) + (26+2) = 56 + 28 = 84
        $this->assertSame(84, $result['spm_p_abs']);

        // 4. spm_total = 59 + 84 = 143
        $this->assertSame(143, $result['spm_total']);

        // 5. spm_pct = 143 / 616 * 100 = 23.2142857... -> 23.21
        $this->assertEqualsWithDelta(23.21, $result['spm_pct'], 0.001);

        // 6. Total kunjungan
        $this->assertSame(181, $result['total_in']);
        $this->assertSame(47, $result['total_out']);
        $this->assertSame(228, $result['total_kunjungan_bulan']);
    }

    /**
     * Uji pembagi nol: jika sasaran lansia >60 adalah 0, maka persentase wajib 0.0 (bukan error).
     */
    public function test_zero_division_returns_zero_percent(): void
    {
        $input = [
            'sas_a6069_l' => 0,
            'sas_a6069_p' => 0,
            'sas_a70_l'   => 0,
            'sas_a70_p'   => 0,
            'in_a6069_l_baru' => 10,
        ];

        $result = LansiaCalculator::calculateKunjunganRow($input);

        $this->assertSame(0, $result['total_lansia_60']);
        $this->assertSame(10, $result['spm_total']);
        $this->assertSame(0.0, $result['spm_pct']);
    }

    /**
     * Uji baris JUMLAH: persentase baris JUMLAH harus dihitung ulang dari
     * spm_total_jumlah / total_lansia_60_jumlah * 100, bukan rata-rata persen kelurahan.
     */
    public function test_summary_row_recalculates_percentage_not_average(): void
    {
        // Kelurahan 1: 10/100 = 10.00%
        $row1 = [
            'sas_a6069_l' => 50,
            'sas_a6069_p' => 50,
            'sas_a70_l'   => 0,
            'sas_a70_p'   => 0,
            'in_a6069_l_baru' => 10,
        ];

        // Kelurahan 2: 50/100 = 50.00%
        // Jika rata-rata persen = (10 + 50) / 2 = 30.00%
        // Namun jika dihitung dari jumlah: (10 + 50) / (100 + 100) * 100 = 60/200 = 30.00%
        // Mari kita buat pembagi berbeda agar beda dengan rata-rata:
        // Kelurahan 2: 90/300 = 30.00%
        // Rata-rata persen = (10% + 30%) / 2 = 20.00%
        // Hitung ulang dari jumlah: (10 + 90) / (100 + 300) = 100 / 400 = 25.00%!
        $row2 = [
            'sas_a6069_l' => 150,
            'sas_a6069_p' => 150,
            'sas_a70_l'   => 0,
            'sas_a70_p'   => 0,
            'in_a6069_l_baru' => 90,
        ];

        $summary = LansiaCalculator::calculateSummaryRow([$row1, $row2]);

        $this->assertSame(400, $summary['total_lansia_60']);
        $this->assertSame(100, $summary['spm_total']);
        // Harus 25.00%, BUKAN rata-rata 20.00%!
        $this->assertEqualsWithDelta(25.00, $summary['spm_pct'], 0.001);
    }

    /**
     * Uji nilai null dianggap 0 dan tidak memicu warning PHP
     */
    public function test_null_values_treated_as_zero_without_error(): void
    {
        $input = [
            'sas_a6069_l' => null,
            'in_a6069_l_baru' => null,
        ];

        $result = LansiaCalculator::calculateKunjunganRow($input);

        $this->assertSame(0, $result['total_lansia_60']);
        $this->assertSame(0, $result['spm_total']);
        $this->assertSame(0.0, $result['spm_pct']);
    }

    /**
     * Uji kalkulasi kelainan & tindakan pada form Layanan
     */
    public function test_layanan_calculation(): void
    {
        $input = [
            'kel_dm_l'         => 12,
            'kel_dm_p'         => 18,
            'kel_td_tinggi_l'  => 15,
            'kel_td_tinggi_p'  => 25,
            'pengobatan_obati' => 20,
            'konseling_baru'   => 5,
        ];

        $result = LansiaCalculator::calculateLayananRow($input);

        $this->assertSame(27, $result['kelainan_total_l']); // 12 + 15
        $this->assertSame(43, $result['kelainan_total_p']); // 18 + 25
        $this->assertSame(70, $result['kelainan_total']);   // 27 + 43
        $this->assertSame(30, $result['kelainan_by_key']['dm']['total']);
        $this->assertSame(40, $result['kelainan_by_key']['td_tinggi']['total']);
        $this->assertSame(25, $result['tindakan_total']);   // 20 + 5
    }

    /**
     * Uji format persentase gaya Indonesia (koma 2 desimal)
     */
    public function test_format_percentage_indonesia(): void
    {
        $this->assertSame('23,21 %', LansiaCalculator::formatPercentage(23.21));
        $this->assertSame('23,21', LansiaCalculator::formatPercentage(23.21, false));
        $this->assertSame('0,00 %', LansiaCalculator::formatPercentage(0.0));
    }

    /**
     * Uji deteksi anomali / peringatan bisnis
     */
    public function test_anomalies_warnings(): void
    {
        // Kasus: SPM > 100% dan kunjungan > sasaran
        $kunj = [
            'sas_a6069_l'     => 10,
            'in_a6069_l_baru' => 25,
        ];

        $warnings = LansiaCalculator::checkWarnings($kunj);

        $this->assertNotEmpty($warnings);
        $this->assertTrue(collect($warnings)->contains(fn ($w) => str_contains($w, 'melebihi 100%')));
        $this->assertTrue(collect($warnings)->contains(fn ($w) => str_contains($w, 'lebih besar dari sasaran')));
    }
}
