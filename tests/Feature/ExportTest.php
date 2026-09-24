<?php

namespace Tests\Feature;

use App\Actions\CreateMonthlyReport;
use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoReportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Memastikan guest tidak dapat mengakses rute ekspor.
     */
    public function test_guest_cannot_access_export(): void
    {
        $this->get('/ekspor')->assertRedirect('/login');
        $this->get('/ekspor/download?year=2026')->assertRedirect('/login');
    }

    /**
     * Memastikan halaman ekspor dapat dirender dengan prop yang sesuai.
     */
    public function test_export_index_renders_with_months_and_years(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/ekspor?year=2026');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Export')
            ->where('selectedYear', 2026)
            ->has('availableYears')
            ->has('months', 12)
        );
    }

    /**
     * Memastikan ekspor 1 bulan menghasilkan file Excel valid dengan perbaikan template dan nilai uji emas.
     */
    public function test_download_single_month_generates_valid_excel_with_golden_values(): void
    {
        $this->seed(DemoReportSeeder::class);
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/ekspor/download?year=2026&month=1');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString(
            'Laporan_Lansia_Payakumbuh_2026_JANUARI.xlsx',
            $response->headers->get('content-disposition')
        );

        // Ambil path file dari BinaryFileResponse untuk diverifikasi dengan PhpSpreadsheet
        $filePath = $response->getFile()->getPathname();

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($filePath);

            // 1. Memastikan Sheet1 (scratchpad) telah dihapus dan tersisa 2 sheet resmi
            $this->assertCount(2, $spreadsheet->getAllSheets());
            $sheetNames = array_map(fn ($s) => trim($s->getTitle()), $spreadsheet->getAllSheets());
            $this->assertContains('Lap. Kunjungan', $sheetNames);
            $this->assertContains('Lap. Layanan Lansia', $sheetNames);

            // Matikan cache kalkulasi agar formula dievaluasi secara dinamis
            Calculation::getInstance($spreadsheet)->disableCalculationCache();

            // 2. Verifikasi Sheet Kunjungan
            $sheetK = $spreadsheet->getSheetByName('Lap. Kunjungan  ') ?? $spreadsheet->getSheet(0);

            // Fix #1: C159 harus ': AGUSTUS'
            $this->assertSame(': AGUSTUS', $sheetK->getCell('C159')->getValue());

            // Fix #3: Nomor urut kelurahan ke-6 TALANG adalah 6 (baris 17 untuk Januari)
            $this->assertEquals(6, $sheetK->getCell('A17')->getValue());
            $this->assertSame('TALANG', $sheetK->getCell('B17')->getValue());

            // Uji Emas Payolansek (Januari baris 12):
            // W12 adalah in_a6069_p_baru = 40
            $this->assertEquals(40, $sheetK->getCell('W12')->getValue());

            // Evaluasi Formula BN12 (SPM Total Lansia >60) == 143
            $this->assertEquals(143, $sheetK->getCell('BN12')->getCalculatedValue());

            // Evaluasi Formula BO12 (Capaian SPM %) == 23.21 % (23.2142857...)
            $bo12 = (float) $sheetK->getCell('BO12')->getCalculatedValue();
            $this->assertEqualsWithDelta(23.21, $bo12, 0.01);

            // 3. Verifikasi Sheet Layanan Lansia
            $sheetL = $spreadsheet->getSheetByName('Lap. Layanan Lansia ') ?? $spreadsheet->getSheet(1);

            // Fix #2: Header baris 1 mencerminkan TA. 2026
            $this->assertStringContainsString('TA. 2026', (string) $sheetL->getCell('A1')->getValue());

            // Fix #3: Nomor urut kelurahan ke-6 TALANG adalah 6
            $this->assertEquals(6, $sheetL->getCell('A17')->getValue());

            // Fix #4: Formula AM12 hanya menjumlahkan kolom perempuan (K, M, O, Q, S, U, W, Y, AA, AC, AE, AG, AI, AK)
            $amFormula = (string) $sheetL->getCell('AM12')->getValue();
            $this->assertStringStartsWith('=', $amFormula);
            $this->assertSame(
                '=K12+M12+O12+Q12+S12+U12+W12+Y12+AA12+AC12+AE12+AG12+AI12+AK12',
                $amFormula
            );
            // Nilai terhitung AM12 (total kelainan perempuan Payolansek: 24+14+19+16) == 73
            $this->assertEquals(73, $sheetL->getCell('AM12')->getCalculatedValue());
        }

    /**
     * Memastikan opsi ekspor 1 tahun penuh (SEMUA) menghasilkan file yang mencakup seluruh bulan.
     */
    public function test_download_full_year_generates_valid_excel(): void
    {
        $this->seed(DemoReportSeeder::class);
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/ekspor/download?year=2026');

        $response->assertOk();
        $this->assertStringContainsString(
            'Laporan_Lansia_Payakumbuh_2026_SEMUA.xlsx',
            $response->headers->get('content-disposition')
        );

        $filePath = $response->getFile()->getPathname();

        $reader = new XlsxReader();
        $spreadsheet = $reader->load($filePath);
        $this->assertCount(2, $spreadsheet->getAllSheets());

        $sheetK = $spreadsheet->getSheet(0);
        $this->assertEquals(40, $sheetK->getCell('W12')->getValue());
    }
}
