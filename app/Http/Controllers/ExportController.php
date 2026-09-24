<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Services\ReportExporter;
use App\Services\StatistikService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function index(Request $request): Response
    {
        $currentYear = (int) date('Y');
        $year = (int) $request->input('year', $currentYear);
        if ($year < 2020 || $year > 2050) {
            $year = $currentYear;
        }

        $distinctYears = MonthlyReport::select('year')
            ->distinct()
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->all();

        if (! in_array($currentYear, $distinctYears, true)) {
            $distinctYears[] = $currentYear;
        }
        if (! in_array($year, $distinctYears, true)) {
            $distinctYears[] = $year;
        }
        rsort($distinctYears);

        $reports = MonthlyReport::where('year', $year)
            ->get(['id', 'year', 'month', 'status', 'finalized_at'])
            ->keyBy('month');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $report = $reports->get($m);
            $months[] = [
                'month'        => $m,
                'name'         => StatistikService::MONTH_NAMES[$m]['full'],
                'has_report'   => $report !== null,
                'status'       => $report?->status,
                'finalized_at' => $report?->finalized_at?->format('d/m/Y H:i'),
            ];
        }

        return Inertia::render('Export', [
            'selectedYear'   => $year,
            'availableYears' => array_values(array_unique($distinctYears)),
            'months'         => $months,
        ]);
    }

    public function download(Request $request, ReportExporter $exporter): BinaryFileResponse
    {
        $request->validate([
            'year'  => 'required|integer|min:2020|max:2050',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $year = (int) $request->input('year');
        $month = $request->filled('month') ? (int) $request->input('month') : null;

        $result = $exporter->export($year, $month);

        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend(true);
    }
}
