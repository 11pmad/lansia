<?php

namespace Tests\Feature;

use App\Actions\CreateMonthlyReport;
use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Models\User;
use App\Services\LansiaCalculator;
use App\Services\StatistikService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatistikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Memastikan StatistikService mengembalikan 12 bulan dan bulan kosong sebagai null (bukan 0).
     */
    public function test_empty_months_return_null_not_zero(): void
    {
        $service = app(StatistikService::class);
        $data = $service->getAnnualData(2026);

        $this->assertSame(2026, $data['year']);
        $this->assertCount(12, $data['trend']);
        $this->assertCount(12, $data['spm']);

        // Tidak ada laporan sama sekali di 2026 -> seluruh bulan harus null
        foreach ($data['trend'] as $item) {
            $this->assertNull($item['total'], "Bulan {$item['month']} total harus null jika kosong");
            $this->assertNull($item['in']);
            $this->assertNull($item['out']);
        }

        foreach ($data['spm'] as $item) {
            $this->assertNull($item['l_abs']);
            $this->assertNull($item['p_abs']);
            $this->assertNull($item['total']);
            $this->assertNull($item['pct']);
        }

        $this->assertFalse($data['summary']['has_any_data']);
    }

    /**
     * Memastikan angka grafik sama persis dengan angka rekap dan uji emas Payolansek.
     */
    public function test_chart_numbers_match_recap_and_golden_test(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();
        $action = app(CreateMonthlyReport::class);

        // Buat laporan Januari 2026
        $report = $action->execute($puskesmas, 2026, 1, $petugas);

        // Isi baris PAYOLANSEK dengan data uji emas
        $payoRow = $report->kunjunganRows->firstWhere('kelurahan_id', $payolansek->id);
        $payoRow->update([
            'sas_a4559_l'     => 200,
            'sas_a4559_p'     => 243,
            'sas_a6069_l'     => 180,
            'sas_a6069_p'     => 209,
            'sas_a70_l'       => 110,
            'sas_a70_p'       => 117,
            'in_a4559_l_baru' => 20,
            'in_a4559_p_baru' => 50,
            'in_a6069_l_baru' => 16,
            'in_a6069_p_baru' => 40,
            'in_a70_l_baru'   => 29,
            'in_a70_p_baru'   => 26,
            'out_a4559_l_baru'=> 5,
            'out_a4559_p_baru'=> 10,
            'out_a6069_l_baru'=> 14,
            'out_a6069_p_baru'=> 16,
            'out_a70_l_baru'  => 0,
            'out_a70_p_baru'  => 2,
        ]);

        $service = app(StatistikService::class);

        // 1. Uji filter kelurahan PAYOLANSEK
        $payoStats = $service->getAnnualData(2026, $payolansek->id);
        $janPayoTrend = $payoStats['trend'][0]; // index 0 = bulan 1 (Januari)
        $janPayoSpm = $payoStats['spm'][0];

        // Verifikasi Uji Emas di grafik SPM
        $this->assertSame(59, $janPayoSpm['l_abs']);
        $this->assertSame(84, $janPayoSpm['p_abs']);
        $this->assertSame(143, $janPayoSpm['total']);
        $this->assertSame(23.21, $janPayoSpm['pct']);

        // Verifikasi Kunjungan
        $this->assertSame(181, $janPayoTrend['in']);
        $this->assertSame(47, $janPayoTrend['out']);
        $this->assertSame(228, $janPayoTrend['total']);
        $this->assertSame(228, $janPayoTrend['in'] + $janPayoTrend['out']);

        // 2. Uji total Puskesmas (semua kelurahan)
        $totalStats = $service->getAnnualData(2026, null);
        $janTotalTrend = $totalStats['trend'][0];
        $janTotalSpm = $totalStats['spm'][0];

        // Karena kelurahan lain belum diisi, total harus sama persis dengan baris JUMLAH rekapitulasi
        $recapSummary = LansiaCalculator::calculateSummaryRow($report->fresh()->kunjunganRows->all());

        $this->assertSame($recapSummary['total_kunjungan_bulan'], $janTotalTrend['total']);
        $this->assertSame($recapSummary['total_in'], $janTotalTrend['in']);
        $this->assertSame($recapSummary['total_out'], $janTotalTrend['out']);
        $this->assertSame($recapSummary['spm_l_abs'], $janTotalSpm['l_abs']);
        $this->assertSame($recapSummary['spm_p_abs'], $janTotalSpm['p_abs']);
        $this->assertSame($recapSummary['spm_total'], $janTotalSpm['total']);
        $this->assertSame($recapSummary['spm_pct'], $janTotalSpm['pct']);

        // Bulan 2..12 harus tetap null (celah bukan 0)
        for ($m = 1; $m < 12; $m++) {
            $this->assertNull($totalStats['trend'][$m]['total']);
            $this->assertNull($totalStats['spm'][$m]['total']);
        }
    }

    /**
     * Memastikan rute /statistik dapat diakses dengan filter dan mengembalikan props yang benar.
     */
    public function test_statistik_page_renders_with_filters(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        $response = $this->actingAs($petugas)->get('/statistik?year=2026&kelurahan_id=' . $payolansek->id);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Statistik')
            ->has('trend', 12)
            ->has('spm', 12)
            ->has('summary')
            ->has('kelurahans')
            ->where('year', 2026)
            ->where('kelurahanId', $payolansek->id)
        );
    }

    /**
     * Memastikan dashboard menampilkan data ringkasan SPM dan mini trend.
     */
    public function test_dashboard_renders_stats_and_mini_trend(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        // Buat laporan bulan berjalan
        $currentMonth = (int) date('n');
        $currentYear = (int) date('Y');
        $action = app(CreateMonthlyReport::class);
        $report = $action->execute($puskesmas, $currentYear, $currentMonth, $petugas);

        // Isi data kunjungan pada salah satu kelurahan
        $payoRow = $report->kunjunganRows->firstWhere('kelurahan_id', $payolansek->id);
        $payoRow->update([
            'sas_a6069_l'     => 100,
            'sas_a6069_p'     => 100,
            'in_a6069_l_baru' => 10,
            'in_a6069_p_baru' => 15,
        ]);

        $response = $this->actingAs($petugas)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('dashboardData', fn (Assert $data) => $data
                ->where('year', $currentYear)
                ->where('month', $currentMonth)
                ->where('report_status', 'draft')
                ->where('filled_kelurahans', 1)
                ->where('current_spm_total', 25)
                ->where('current_spm_pct', 12.5) // 25 / 200 * 100
                ->has('mini_trend', 12)
                ->etc()
            )
        );
    }
}

