<?php

namespace App\Actions;

use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\LayananRow;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Models\User;
use App\Support\LansiaFields;
use Illuminate\Support\Facades\DB;

class CreateMonthlyReport
{
    /**
     * Buat laporan bulanan baru, buat 6 baris kunjungan & 6 baris layanan,
     * serta salin data sasaran/posyandu/kader dari bulan sebelumnya bila ada.
     *
     * @param Puskesmas $puskesmas
     * @param int $year
     * @param int $month
     * @param User $creator
     * @return MonthlyReport
     */
    public function execute(Puskesmas $puskesmas, int $year, int $month, User $creator): MonthlyReport
    {
        return DB::transaction(function () use ($puskesmas, $year, $month, $creator) {
            // Cek apakah laporan sudah ada
            $report = MonthlyReport::where('puskesmas_id', $puskesmas->id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($report) {
                return $report;
            }

            // Cari laporan bulan sebelumnya untuk penyalinan data sasaran/posyandu/kader
            $prevMonth = $month === 1 ? 12 : $month - 1;
            $prevYear = $month === 1 ? $year - 1 : $year;

            $prevReport = MonthlyReport::where('puskesmas_id', $puskesmas->id)
                ->where('year', $prevYear)
                ->where('month', $prevMonth)
                ->with(['kunjunganRows', 'layananRows'])
                ->first();

            // Buat laporan baru
            $report = MonthlyReport::create([
                'puskesmas_id' => $puskesmas->id,
                'year'         => $year,
                'month'        => $month,
                'status'       => 'draft',
                'created_by'   => $creator->id,
                'updated_by'   => $creator->id,
            ]);

            // Ambil 6 kelurahan aktif sesuai urutan
            $kelurahans = Kelurahan::where('puskesmas_id', $puskesmas->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $colsToCopyKunjungan = array_merge(
                LansiaFields::kunjunganUmumColumns(),
                LansiaFields::sasaranColumns()
            );

            $colsToCopyLayanan = LansiaFields::sasaranColumns();

            foreach ($kelurahans as $kelurahan) {
                $prevKunjRow = $prevReport?->kunjunganRows->firstWhere('kelurahan_id', $kelurahan->id);
                $prevLayRow = $prevReport?->layananRows->firstWhere('kelurahan_id', $kelurahan->id);

                // Buat kunjungan_row baru
                $kunjData = [
                    'monthly_report_id' => $report->id,
                    'kelurahan_id'      => $kelurahan->id,
                ];

                if ($prevKunjRow) {
                    foreach ($colsToCopyKunjungan as $col) {
                        $kunjData[$col] = $prevKunjRow->{$col};
                    }
                }

                KunjunganRow::create($kunjData);

                // Buat layanan_row baru
                $layData = [
                    'monthly_report_id' => $report->id,
                    'kelurahan_id'      => $kelurahan->id,
                ];

                if ($prevLayRow) {
                    foreach ($colsToCopyLayanan as $col) {
                        $layData[$col] = $prevLayRow->{$col};
                    }
                }

                LayananRow::create($layData);
            }

            return $report;
        });
    }
}
