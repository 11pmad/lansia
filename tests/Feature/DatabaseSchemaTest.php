<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\LayananRow;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_populate_puskesmas_and_six_kelurahans(): void
    {
        $this->seed(DatabaseSeeder::class);

        $puskesmas = Puskesmas::first();
        $this->assertNotNull($puskesmas);
        $this->assertSame('PAYOLANSEK', $puskesmas->name);
        $this->assertSame('PAYAKUMBUH', $puskesmas->city);

        $kelurahans = $puskesmas->kelurahans;
        $this->assertCount(6, $kelurahans);

        $expectedNames = ['PAYOLANSEK', 'BULBA', 'PAKAN SINAYAN', 'KUBU GADANG', 'KOTO TANGAH', 'TALANG'];
        $this->assertSame($expectedNames, $kelurahans->pluck('name')->toArray());
    }

    public function test_monthly_report_and_rows_creation_and_cascade_delete(): void
    {
        $this->seed(DatabaseSeeder::class);

        $puskesmas = Puskesmas::first();
        $kelurahan = $puskesmas->kelurahans->first();

        // Buat monthly report
        $report = MonthlyReport::create([
            'puskesmas_id' => $puskesmas->id,
            'year'         => 2026,
            'month'        => 1,
            'status'       => 'draft',
        ]);

        $this->assertTrue($report->isDraft());
        $this->assertFalse($report->isFinal());

        // Buat kunjungan row
        $kunjungan = KunjunganRow::create([
            'monthly_report_id' => $report->id,
            'kelurahan_id'      => $kelurahan->id,
            'posyandu_count'    => 5,
            'sas_a6069_l'       => 180,
            'in_a6069_l_baru'   => 16,
        ]);

        // Buat layanan row
        $layanan = LayananRow::create([
            'monthly_report_id' => $report->id,
            'kelurahan_id'      => $kelurahan->id,
            'sas_a6069_l'       => 180,
            'kel_dm_l'          => 12,
            'keterangan'        => 'Data Januari 2026',
        ]);

        $this->assertDatabaseHas('kunjungan_rows', [
            'id'             => $kunjungan->id,
            'posyandu_count' => 5,
            'sas_a6069_l'    => 180,
        ]);

        $this->assertDatabaseHas('layanan_rows', [
            'id'         => $layanan->id,
            'kel_dm_l'   => 12,
            'keterangan' => 'Data Januari 2026',
        ]);

        // Hapus report -> harus cascade ke kunjungan_rows & layanan_rows
        $report->delete();

        $this->assertDatabaseMissing('kunjungan_rows', ['id' => $kunjungan->id]);
        $this->assertDatabaseMissing('layanan_rows', ['id' => $layanan->id]);
    }

    public function test_kelurahan_deletion_restricted_when_has_rows(): void
    {
        $this->seed(DatabaseSeeder::class);

        $puskesmas = Puskesmas::first();
        $kelurahan = $puskesmas->kelurahans->first();

        $report = MonthlyReport::create([
            'puskesmas_id' => $puskesmas->id,
            'year'         => 2026,
            'month'        => 1,
            'status'       => 'draft',
        ]);

        KunjunganRow::create([
            'monthly_report_id' => $report->id,
            'kelurahan_id'      => $kelurahan->id,
        ]);

        // Hapus kelurahan yang memiliki relasi data harus melempar QueryException (restrictOnDelete)
        $this->expectException(QueryException::class);
        $kelurahan->delete();
    }
}
