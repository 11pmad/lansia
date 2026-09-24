<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_view_reports_index(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->get('/laporan?year=2026');
        $response->assertOk();
    }

    public function test_user_can_create_monthly_report(): void
    {
        $petugas = User::where('role', 'petugas')->first();

        $response = $this->actingAs($petugas)->post('/laporan', [
            'year'  => 2026,
            'month' => 1,
        ]);

        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();
        $this->assertNotNull($report);
        $this->assertSame('draft', $report->status);

        $response->assertRedirect(route('reports.show', $report->id));

        // Harus membuat 6 baris kunjungan & 6 baris layanan
        $this->assertCount(6, $report->kunjunganRows);
        $this->assertCount(6, $report->layananRows);
    }

    public function test_creating_new_month_copies_targets_from_previous_month(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $puskesmas = Puskesmas::first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        // 1. Buat laporan Januari 2026
        $this->actingAs($petugas)->post('/laporan', [
            'year'  => 2026,
            'month' => 1,
        ]);
        $janReport = MonthlyReport::where('year', 2026)->where('month', 1)->first();

        // Isi sasaran di Januari
        $this->actingAs($petugas)->putJson(
            route('forms.kunjungan.update', ['report' => $janReport->id, 'kelurahan' => $payolansek->id]),
            [
                'posyandu_count' => 6,
                'kader_count'    => 30,
                'sas_a6069_l'    => 180,
                'sas_a6069_p'    => 209,
                'in_a6069_l_baru'=> 16, // Kunjungan Januari
            ]
        );

        // 2. Buat laporan Februari 2026
        $this->actingAs($petugas)->post('/laporan', [
            'year'  => 2026,
            'month' => 2,
        ]);
        $febReport = MonthlyReport::where('year', 2026)->where('month', 2)->first();

        $febPayoRow = $febReport->kunjunganRows->firstWhere('kelurahan_id', $payolansek->id);

        // Sasaran dan posyandu/kader tersalin dari bulan sebelumnya
        $this->assertSame(6, $febPayoRow->posyandu_count);
        $this->assertSame(30, $febPayoRow->kader_count);
        $this->assertSame(180, $febPayoRow->sas_a6069_l);
        $this->assertSame(209, $febPayoRow->sas_a6069_p);

        // Angka kunjungan mulai kosong/null di bulan baru (Aturan 01_SPEC.md § 4.C)
        $this->assertNull($febPayoRow->in_a6069_l_baru);
    }

    public function test_saving_kunjungan_form_and_autosave(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->actingAs($petugas)->post('/laporan', ['year' => 2026, 'month' => 1]);
        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        // Simpan data uji emas
        $response = $this->actingAs($petugas)->putJson(
            route('forms.kunjungan.update', ['report' => $report->id, 'kelurahan' => $payolansek->id]),
            [
                'sas_a4559_l'     => 200,
                'sas_a4559_p'     => 243,
                'sas_a6069_l'     => 180,
                'sas_a6069_p'     => 209,
                'sas_a70_l'       => 110,
                'sas_a70_p'       => 117,
                'in_a6069_l_baru' => 16,
                'out_a6069_l_baru'=> 14,
                'in_a70_l_baru'   => 29,
                'out_a70_l_baru'  => 0,
                'in_a6069_p_baru' => 40,
                'out_a6069_p_baru'=> 16,
                'in_a70_p_baru'   => 26,
                'out_a70_p_baru'  => 2,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('calculations.total_lansia_60', 616);
        $response->assertJsonPath('calculations.spm_l_abs', 59);
        $response->assertJsonPath('calculations.spm_p_abs', 84);
        $response->assertJsonPath('calculations.spm_total', 143);
        $response->assertJsonPath('calculations.spm_pct', 23.21);

        $this->assertDatabaseHas('kunjungan_rows', [
            'monthly_report_id' => $report->id,
            'kelurahan_id'      => $payolansek->id,
            'sas_a6069_l'       => 180,
            'in_a6069_l_baru'   => 16,
        ]);
    }

    public function test_saving_layanan_form(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->actingAs($petugas)->post('/laporan', ['year' => 2026, 'month' => 1]);
        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        $response = $this->actingAs($petugas)->putJson(
            route('forms.layanan.update', ['report' => $report->id, 'kelurahan' => $payolansek->id]),
            [
                'kel_dm_l'         => 12,
                'kel_dm_p'         => 18,
                'pengobatan_obati' => 25,
                'keterangan'       => 'Pelayanan lancar',
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('calculations.kelainan_total_l', 12);
        $response->assertJsonPath('calculations.kelainan_total_p', 18);
        $response->assertJsonPath('calculations.kelainan_total', 30);
        $response->assertJsonPath('calculations.tindakan_total', 25);
    }

    public function test_finalizing_report_locks_editing(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->actingAs($petugas)->post('/laporan', ['year' => 2026, 'month' => 1]);
        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();
        $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();

        // Finalisasi laporan
        $responseFinalize = $this->actingAs($petugas)->post(route('reports.finalize', $report->id));
        $responseFinalize->assertRedirect();

        $report->refresh();
        $this->assertTrue($report->isFinal());
        $this->assertNotNull($report->finalized_at);

        // Coba edit setelah final -> HARUS DITOLAK (403 Forbidden)
        $responseKunjungan = $this->actingAs($petugas)->putJson(
            route('forms.kunjungan.update', ['report' => $report->id, 'kelurahan' => $payolansek->id]),
            ['in_a6069_l_baru' => 50]
        );
        $responseKunjungan->assertForbidden();

        $responseLayanan = $this->actingAs($petugas)->putJson(
            route('forms.layanan.update', ['report' => $report->id, 'kelurahan' => $payolansek->id]),
            ['kel_dm_l' => 20]
        );
        $responseLayanan->assertForbidden();
    }

    public function test_petugas_cannot_reopen_finalized_report_but_admin_can(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $admin = User::where('role', 'admin')->first();

        $this->actingAs($petugas)->post('/laporan', ['year' => 2026, 'month' => 1]);
        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();

        // Finalisasi
        $this->actingAs($petugas)->post(route('reports.finalize', $report->id));
        $report->refresh();
        $this->assertTrue($report->isFinal());

        // Petugas mencoba buka kunci -> DITOLAK (403 Forbidden)
        $this->actingAs($petugas)->post(route('reports.reopen', $report->id))->assertForbidden();
        $report->refresh();
        $this->assertTrue($report->isFinal());

        // Admin membuka kunci -> BERHASIL (status kembali ke draft)
        $this->actingAs($admin)->post(route('reports.reopen', $report->id))->assertRedirect();
        $report->refresh();
        $this->assertTrue($report->isDraft());
        $this->assertNull($report->finalized_at);
    }

    public function test_recap_page_can_be_rendered(): void
    {
        $petugas = User::where('role', 'petugas')->first();
        $this->actingAs($petugas)->post('/laporan', ['year' => 2026, 'month' => 1]);
        $report = MonthlyReport::where('year', 2026)->where('month', 1)->first();

        $response = $this->actingAs($petugas)->get(route('reports.recap', $report->id));
        $response->assertOk();
    }
}
