<?php

namespace Tests\Unit;

use App\Support\LansiaFields;
use PHPUnit\Framework\TestCase;

class LansiaFieldsTest extends TestCase
{
    public function test_column_counts_and_structures(): void
    {
        // 6 Sasaran
        $sas = LansiaFields::sasaranColumns();
        $this->assertCount(6, $sas);
        $this->assertContains('sas_a6069_l', $sas);
        $this->assertContains('sas_a70_p', $sas);

        // 12 In & 12 Out => 24 Kunjungan
        $inCols = LansiaFields::kunjunganInColumns();
        $outCols = LansiaFields::kunjunganOutColumns();
        $this->assertCount(12, $inCols);
        $this->assertCount(12, $outCols);
        $this->assertCount(24, LansiaFields::kunjunganColumns());
        $this->assertContains('in_a6069_p_baru', $inCols);
        $this->assertContains('out_a70_l_lama', $outCols);

        // 6 Mandiri
        $mandiri = LansiaFields::mandiriColumns();
        $this->assertCount(6, $mandiri);
        $this->assertContains('mandiri_6069_a', $mandiri);
        $this->assertContains('mandiri_70_c', $mandiri);

        // Seluruh kunjungan_rows = 6 umum + 6 sasaran + 24 kunjungan + 6 mandiri = 42
        $this->assertCount(42, LansiaFields::allKunjunganRowColumns());

        // 28 Kelainan (14 key x 2 sex)
        $kelCols = LansiaFields::kelainanColumns();
        $this->assertCount(28, $kelCols);
        $this->assertContains('kel_dm_l', $kelCols);
        $this->assertContains('kel_td_tinggi_p', $kelCols);

        // Seluruh layanan_rows = 6 sasaran + 28 kelainan + 7 tindakan + 4 lain = 45
        $this->assertCount(45, LansiaFields::allLayananRowColumns());
    }

    public function test_labels_are_in_indonesian(): void
    {
        $this->assertSame('Kunjungan Dalam Gedung 60–69 Th (Perempuan - Baru)', LansiaFields::label('in_a6069_p_baru'));
        $this->assertSame('Diabetes Melitus / Gula Darah Tinggi (Laki-laki)', LansiaFields::label('kel_dm_l'));
        $this->assertSame('Jumlah Posyandu Lansia', LansiaFields::label('posyandu_count'));
    }

    public function test_section_fields_for_ocr(): void
    {
        $kunjIn = LansiaFields::sectionFields('kunjungan_dalam');
        $this->assertCount(12, $kunjIn);
        $this->assertContains('in_a4559_l_lama', $kunjIn);

        $layananKel = LansiaFields::sectionFields('layanan_sasaran_kelainan');
        // 6 sasaran + 28 kelainan = 34
        $this->assertCount(34, $layananKel);
    }
}
