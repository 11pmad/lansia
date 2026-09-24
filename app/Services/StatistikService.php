<?php

namespace App\Services;

use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Support\LansiaFields;

class StatistikService
{
    public const MONTH_NAMES = [
        1  => ['short' => 'Jan', 'full' => 'Januari'],
        2  => ['short' => 'Feb', 'full' => 'Februari'],
        3  => ['short' => 'Mar', 'full' => 'Maret'],
        4  => ['short' => 'Apr', 'full' => 'April'],
        5  => ['short' => 'Mei', 'full' => 'Mei'],
        6  => ['short' => 'Jun', 'full' => 'Juni'],
        7  => ['short' => 'Jul', 'full' => 'Juli'],
        8  => ['short' => 'Agt', 'full' => 'Agustus'],
        9  => ['short' => 'Sep', 'full' => 'September'],
        10 => ['short' => 'Okt', 'full' => 'Oktober'],
        11 => ['short' => 'Nov', 'full' => 'November'],
        12 => ['short' => 'Des', 'full' => 'Desember'],
    ];

    /**
     * Dapatkan data statistik tahunan untuk tren kunjungan dan capaian SPM.
     * Sesuai aturan: bulan tanpa data dikembalikan sebagai null (bukan 0).
     *
     * @param int $year
     * @param int|null $kelurahanId
     * @return array
     */
    public function getAnnualData(int $year, ?int $kelurahanId = null): array
    {
        $reports = MonthlyReport::where('year', $year)
            ->with(['kunjunganRows' => function ($q) use ($kelurahanId) {
                if ($kelurahanId) {
                    $q->where('kelurahan_id', $kelurahanId);
                }
            }])
            ->get()
            ->keyBy('month');

        $trend = [];
        $spm = [];
        $totalVisitsYtd = 0;
        $totalSpmYtd = 0;
        $latestMonthWithData = null;
        $latestSpmPct = null;
        $latestSpmTotal = null;
        $activeTarget60 = 0;

        $inCols = LansiaFields::kunjunganInColumns();
        $outCols = LansiaFields::kunjunganOutColumns();
        $allVisitCols = array_merge($inCols, $outCols);

        for ($m = 1; $m <= 12; $m++) {
            $monthInfo = self::MONTH_NAMES[$m];
            $report = $reports->get($m);

            $hasData = false;
            $calc = null;

            if ($report && $report->kunjunganRows->isNotEmpty()) {
                // Periksa apakah baris memiliki setidaknya satu kolom kunjungan yang bukan null
                foreach ($report->kunjunganRows as $row) {
                    foreach ($allVisitCols as $col) {
                        if ($row->{$col} !== null) {
                            $hasData = true;
                            break 2;
                        }
                    }
                }

                if ($hasData) {
                    if ($kelurahanId) {
                        $singleRow = $report->kunjunganRows->first();
                        $calc = LansiaCalculator::calculateKunjunganRow(
                            $singleRow->only(LansiaFields::allKunjunganRowColumns())
                        );
                    } else {
                        $calc = LansiaCalculator::calculateSummaryRow(
                            $report->kunjunganRows->all()
                        );
                    }
                }
            }

            if ($hasData && $calc) {
                $monthTotal = $calc['total_kunjungan_bulan'];
                $monthIn = $calc['total_in'];
                $monthOut = $calc['total_out'];
                $spmLabs = $calc['spm_l_abs'];
                $spmPabs = $calc['spm_p_abs'];
                $spmTotal = $calc['spm_total'];
                $spmPct = $calc['spm_pct'];
                $target60 = $calc['total_lansia_60'];

                $trend[] = [
                    'month'           => $m,
                    'name'            => $monthInfo['short'],
                    'full_name'       => $monthInfo['full'],
                    'total'           => $monthTotal,
                    'in'              => $monthIn,
                    'out'             => $monthOut,
                ];

                $spm[] = [
                    'month'           => $m,
                    'name'            => $monthInfo['short'],
                    'full_name'       => $monthInfo['full'],
                    'l_abs'           => $spmLabs,
                    'p_abs'           => $spmPabs,
                    'total'           => $spmTotal,
                    'pct'             => $spmPct,
                    'target_total'    => $target60,
                ];

                $totalVisitsYtd += $monthTotal;
                $totalSpmYtd += $spmTotal;
                $latestMonthWithData = $m;
                $latestSpmPct = $spmPct;
                $latestSpmTotal = $spmTotal;
                $activeTarget60 = $target60;
            } else {
                // Bulan tanpa data: kembalikan null (celah pada grafik, bukan 0)
                $trend[] = [
                    'month'     => $m,
                    'name'      => $monthInfo['short'],
                    'full_name' => $monthInfo['full'],
                    'total'     => null,
                    'in'        => null,
                    'out'       => null,
                ];

                $spm[] = [
                    'month'        => $m,
                    'name'         => $monthInfo['short'],
                    'full_name'    => $monthInfo['full'],
                    'l_abs'        => null,
                    'p_abs'        => null,
                    'total'        => null,
                    'pct'          => null,
                    'target_total' => null,
                ];
            }
        }

        return [
            'year'         => $year,
            'kelurahan_id' => $kelurahanId,
            'trend'        => $trend,
            'spm'          => $spm,
            'summary'      => [
                'total_visits_ytd'     => $totalVisitsYtd,
                'total_spm_ytd'        => $totalSpmYtd,
                'latest_month'         => $latestMonthWithData,
                'latest_month_name'    => $latestMonthWithData ? self::MONTH_NAMES[$latestMonthWithData]['full'] : null,
                'latest_spm_pct'       => $latestSpmPct,
                'latest_spm_total'     => $latestSpmTotal,
                'target_lansia_60'     => $activeTarget60,
                'has_any_data'         => $latestMonthWithData !== null,
            ],
        ];
    }

    /**
     * Dapatkan ringkasan statistik untuk dashboard beranda.
     *
     * @param int|null $year
     * @param int|null $month
     * @return array
     */
    public function getDashboardSummary(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? (int) date('Y');
        $month = $month ?? (int) date('n');

        $report = MonthlyReport::where('year', $year)
            ->where('month', $month)
            ->with(['kunjunganRows'])
            ->first();

        $totalKelurahans = Kelurahan::where('is_active', true)->count();
        $filledKelurahans = 0;
        $currentSpmPct = null;
        $currentSpmTotal = null;
        $currentTotalVisits = null;
        $reportStatus = 'empty'; // empty | draft | final

        $inCols = LansiaFields::kunjunganInColumns();
        $outCols = LansiaFields::kunjunganOutColumns();
        $allVisitCols = array_merge($inCols, $outCols);

        if ($report) {
            $reportStatus = $report->status;

            foreach ($report->kunjunganRows as $row) {
                $rowFilled = false;
                foreach ($allVisitCols as $col) {
                    if ($row->{$col} !== null) {
                        $rowFilled = true;
                        break;
                    }
                }
                if ($rowFilled) {
                    $filledKelurahans++;
                }
            }

            if ($filledKelurahans > 0) {
                $summary = LansiaCalculator::calculateSummaryRow($report->kunjunganRows->all());
                $currentSpmPct = $summary['spm_pct'];
                $currentSpmTotal = $summary['spm_total'];
                $currentTotalVisits = $summary['total_kunjungan_bulan'];
            }
        }

        // Tren mini 12 bulan (hanya total kunjungan untuk sparkline/mini chart)
        $annualData = $this->getAnnualData($year, null);
        $miniTrend = array_map(function ($item) {
            return [
                'name'  => $item['name'],
                'total' => $item['total'],
            ];
        }, $annualData['trend']);

        return [
            'year'                => $year,
            'month'               => $month,
            'month_name'          => self::MONTH_NAMES[$month]['full'],
            'report_id'           => $report?->id,
            'report_status'       => $reportStatus,
            'total_kelurahans'    => $totalKelurahans,
            'filled_kelurahans'   => $filledKelurahans,
            'current_spm_pct'     => $currentSpmPct,
            'current_spm_total'   => $currentSpmTotal,
            'current_total_visits'=> $currentTotalVisits,
            'mini_trend'          => $miniTrend,
        ];
    }
}

