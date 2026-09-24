<?php

namespace Database\Seeders;

use App\Actions\CreateMonthlyReport;
use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoReportSeeder extends Seeder
{
    public function run(): void
    {
        $puskesmas = Puskesmas::first();
        if (! $puskesmas) {
            return;
        }

        $creator = User::where('role', 'petugas')->first()
            ?? User::first();

        $action = app(CreateMonthlyReport::class);

        // 1. Buat Laporan Januari 2026 (dengan data uji emas Payolansek)
        if (! MonthlyReport::where('year', 2026)->where('month', 1)->exists()) {
            $janReport = $action->execute($puskesmas, 2026, 1, $creator);

            $payolansek = Kelurahan::where('name', 'PAYOLANSEK')->first();
            if ($payolansek) {
                $payoRow = $janReport->kunjunganRows->firstWhere('kelurahan_id', $payolansek->id);
                if ($payoRow) {
                    $payoRow->update([
                        'posyandu_count'   => 6,
                        'kader_count'      => 30,
                        'sas_a4559_l'      => 200,
                        'sas_a4559_p'      => 243,
                        'sas_a6069_l'      => 180,
                        'sas_a6069_p'      => 209,
                        'sas_a70_l'        => 110,
                        'sas_a70_p'        => 117,
                        'in_a4559_l_baru'  => 20,
                        'in_a4559_p_baru'  => 50,
                        'in_a6069_l_baru'  => 16,
                        'in_a6069_p_baru'  => 40,
                        'in_a70_l_baru'    => 29,
                        'in_a70_p_baru'    => 26,
                        'out_a4559_l_baru' => 5,
                        'out_a4559_p_baru' => 10,
                        'out_a6069_l_baru' => 14,
                        'out_a6069_p_baru' => 16,
                        'out_a70_l_baru'   => 0,
                        'out_a70_p_baru'   => 2,
                        'mandiri_6069_a'   => 65,
                        'mandiri_6069_b'   => 18,
                        'mandiri_6069_c'   => 3,
                        'mandiri_70_a'     => 32,
                        'mandiri_70_b'     => 21,
                        'mandiri_70_c'     => 4,
                    ]);
                }

                $payoLayanan = $janReport->layananRows->firstWhere('kelurahan_id', $payolansek->id);
                if ($payoLayanan) {
                    $payoLayanan->update([
                        'kel_td_tinggi_l'  => 18,
                        'kel_td_tinggi_p'  => 24,
                        'kel_dm_l'         => 8,
                        'kel_dm_p'         => 14,
                        'kel_asam_urat_l'  => 12,
                        'kel_asam_urat_p'  => 19,
                        'kel_kolesterol_l' => 9,
                        'kel_kolesterol_p' => 16,
                        'konseling_baru'   => 25,
                        'pengobatan_rujuk' => 4,
                    ]);
                }
            }

            // Isi sasaran dasar untuk kelurahan lainnya
            $otherTargets = [
                'BULBA'         => ['posyandu' => 4, 'kader' => 20, '4559_l' => 150, '4559_p' => 180, '6069_l' => 120, '6069_p' => 140, '70_l' => 80, '70_p' => 95],
                'PAKAN SINAYAN' => ['posyandu' => 5, 'kader' => 25, '4559_l' => 170, '4559_p' => 210, '6069_l' => 140, '6069_p' => 165, '70_l' => 90, '70_p' => 105],
                'KUBU GADANG'   => ['posyandu' => 4, 'kader' => 20, '4559_l' => 130, '4559_p' => 160, '6069_l' => 110, '6069_p' => 130, '70_l' => 70, '70_p' => 85],
                'KOTO TANGAH'   => ['posyandu' => 5, 'kader' => 25, '4559_l' => 160, '4559_p' => 190, '6069_l' => 135, '6069_p' => 155, '70_l' => 85, '70_p' => 100],
                'TALANG'        => ['posyandu' => 4, 'kader' => 20, '4559_l' => 140, '4559_p' => 170, '6069_l' => 115, '6069_p' => 135, '70_l' => 75, '70_p' => 90],
            ];

            foreach ($otherTargets as $kelName => $tgt) {
                $kel = Kelurahan::where('name', $kelName)->first();
                if ($kel) {
                    $row = $janReport->kunjunganRows->firstWhere('kelurahan_id', $kel->id);
                    if ($row) {
                        $row->update([
                            'posyandu_count' => $tgt['posyandu'],
                            'kader_count'    => $tgt['kader'],
                            'sas_a4559_l'    => $tgt['4559_l'],
                            'sas_a4559_p'    => $tgt['4559_p'],
                            'sas_a6069_l'    => $tgt['6069_l'],
                            'sas_a6069_p'    => $tgt['6069_p'],
                            'sas_a70_l'      => $tgt['70_l'],
                            'sas_a70_p'      => $tgt['70_p'],
                        ]);
                    }
                }
            }
        }
    }
}

