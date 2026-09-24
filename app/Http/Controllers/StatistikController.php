<?php

namespace App\Http\Controllers;

use App\Models\Kelurahan;
use App\Models\MonthlyReport;
use App\Services\StatistikService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatistikController extends Controller
{
    public function index(Request $request, StatistikService $statistikService): Response
    {
        $currentYear = (int) date('Y');
        $year = (int) $request->input('year', $currentYear);
        if ($year < 2020 || $year > 2050) {
            $year = $currentYear;
        }

        $kelurahanId = $request->filled('kelurahan_id')
            ? (int) $request->input('kelurahan_id')
            : null;

        $kelurahans = Kelurahan::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        // Pastikan kelurahanId valid jika diberikan
        if ($kelurahanId && ! $kelurahans->contains('id', $kelurahanId)) {
            $kelurahanId = null;
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

        $stats = $statistikService->getAnnualData($year, $kelurahanId);

        return Inertia::render('Statistik', [
            'year'           => $year,
            'kelurahanId'    => $kelurahanId,
            'availableYears' => array_values(array_unique($distinctYears)),
            'kelurahans'     => $kelurahans,
            'trend'          => $stats['trend'],
            'spm'            => $stats['spm'],
            'summary'        => $stats['summary'],
        ]);
    }
}

