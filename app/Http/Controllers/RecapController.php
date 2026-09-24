<?php

namespace App\Http\Controllers;

use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\LayananRow;
use App\Models\MonthlyReport;
use App\Services\LansiaCalculator;
use App\Support\LansiaFields;
use Inertia\Inertia;
use Inertia\Response;

class RecapController extends Controller
{
    public function show(MonthlyReport $report): Response
    {
        $kelurahans = Kelurahan::where('puskesmas_id', $report->puskesmas_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $kunjunganRows = KunjunganRow::where('monthly_report_id', $report->id)
            ->get()
            ->keyBy('kelurahan_id');

        $layananRows = LayananRow::where('monthly_report_id', $report->id)
            ->get()
            ->keyBy('kelurahan_id');

        $kunjunganData = [];
        $layananData = [];

        foreach ($kelurahans as $kel) {
            $kRow = $kunjunganRows->get($kel->id);
            $kRaw = $kRow ? $kRow->only(LansiaFields::allKunjunganRowColumns()) : [];
            $kCalc = LansiaCalculator::calculateKunjunganRow($kRaw);

            $kunjunganData[] = [
                'kelurahan'    => $kel,
                'raw'          => $kRaw,
                'calculations' => $kCalc,
            ];

            $lRow = $layananRows->get($kel->id);
            $lRaw = $lRow ? $lRow->only(LansiaFields::allLayananRowColumns()) : [];
            $lCalc = LansiaCalculator::calculateLayananRow($lRaw);

            $layananData[] = [
                'kelurahan'    => $kel,
                'raw'          => $lRaw,
                'calculations' => $lCalc,
            ];
        }

        // Hitung baris JUMLAH untuk seluruh kelurahan
        $kunjunganSummary = LansiaCalculator::calculateSummaryRow($kunjunganRows->all());
        $layananSummary = LansiaCalculator::calculateLayananSummaryRow($layananRows->all());

        return Inertia::render('Reports/Recap', [
            'report' => [
                'id'           => $report->id,
                'year'         => $report->year,
                'month'        => $report->month,
                'status'       => $report->status,
                'finalized_at' => $report->finalized_at?->format('d/m/Y H:i'),
            ],
            'kelurahans'       => $kelurahans,
            'kunjunganData'    => $kunjunganData,
            'layananData'      => $layananData,
            'kunjunganSummary' => $kunjunganSummary,
            'layananSummary'   => $layananSummary,
            'kelainanKeys'     => LansiaFields::KELAINAN_KEYS,
        ]);
    }
}
