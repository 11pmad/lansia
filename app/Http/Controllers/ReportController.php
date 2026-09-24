<?php

namespace App\Http\Controllers;

use App\Actions\CreateMonthlyReport;
use App\Models\MonthlyReport;
use App\Models\Puskesmas;
use App\Services\LansiaCalculator;
use App\Support\LansiaFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $puskesmas = Puskesmas::firstOrCreate(['name' => 'PAYOLANSEK'], ['city' => 'PAYAKUMBUH']);
        $selectedYear = (int) $request->input('year', date('Y'));

        // Ambil semua laporan untuk tahun terpilih beserta baris kunjungannya
        $reports = MonthlyReport::where('puskesmas_id', $puskesmas->id)
            ->where('year', $selectedYear)
            ->with(['kunjunganRows', 'layananRows'])
            ->get()
            ->keyBy('month');

        // Bentuk data untuk 12 bulan
        $monthsData = [];
        for ($m = 1; $m <= 12; $m++) {
            $report = $reports->get($m);

            if ($report) {
                // Hitung progres kelengkapan pengisian
                $kunjunganRows = $report->kunjunganRows;
                $filledKelurahans = $kunjunganRows->filter(function ($row) {
                    // Dianggap ada isian jika ada sasaran atau kunjungan > 0
                    return ($row->total_lansia_60 ?? 0) > 0 || collect(LansiaFields::kunjunganColumns())->some(fn ($c) => ($row->{$c} ?? 0) > 0);
                })->count();

                // Hitung total SPM bulan ini
                $summary = LansiaCalculator::calculateSummaryRow($kunjunganRows->all());

                $monthsData[] = [
                    'month'            => $m,
                    'report_id'        => $report->id,
                    'status'           => $report->status,
                    'finalized_at'     => $report->finalized_at?->format('d/m/Y H:i'),
                    'filled_count'     => $filledKelurahans,
                    'total_kelurahan'  => 6,
                    'spm_total'        => $summary['spm_total'] ?? 0,
                    'total_lansia_60'  => $summary['total_lansia_60'] ?? 0,
                    'spm_pct'          => $summary['spm_pct'] ?? 0.0,
                    'total_visit'      => $summary['total_kunjungan_bulan'] ?? 0,
                ];
            } else {
                $monthsData[] = [
                    'month'            => $m,
                    'report_id'        => null,
                    'status'           => 'empty',
                    'finalized_at'     => null,
                    'filled_count'     => 0,
                    'total_kelurahan'  => 6,
                    'spm_total'        => 0,
                    'total_lansia_60'  => 0,
                    'spm_pct'          => 0.0,
                    'total_visit'      => 0,
                ];
            }
        }

        // Daftar tahun yang tersedia
        $availableYears = MonthlyReport::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (!in_array($selectedYear, $availableYears)) {
            $availableYears[] = $selectedYear;
            sort($availableYears);
            $availableYears = array_reverse($availableYears);
        }

        return Inertia::render('Reports/Index', [
            'selectedYear'   => $selectedYear,
            'availableYears' => $availableYears,
            'monthsData'     => $monthsData,
        ]);
    }

    public function store(Request $request, CreateMonthlyReport $createAction): RedirectResponse
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer', 'between:2020,2035'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $puskesmas = Puskesmas::firstOrCreate(['name' => 'PAYOLANSEK'], ['city' => 'PAYAKUMBUH']);

        $report = $createAction->execute(
            $puskesmas,
            (int) $validated['year'],
            (int) $validated['month'],
            $request->user()
        );

        return redirect()->route('reports.show', $report->id)
            ->with('success', "Laporan bulan {$report->month} tahun {$report->year} berhasil disiapkan.");
    }

    public function show(MonthlyReport $report): Response
    {
        $report->load([
            'kunjunganRows.kelurahan',
            'layananRows.kelurahan',
            'creator',
            'updater',
        ]);

        // Hitung baris summary kunjungan & layanan
        $kunjunganSummary = LansiaCalculator::calculateSummaryRow($report->kunjunganRows->all());
        $layananSummary = LansiaCalculator::calculateLayananSummaryRow($report->layananRows->all());

        // Cek peringatan / anomali validasi sebelum finalisasi
        $warnings = LansiaCalculator::checkWarnings($kunjunganSummary, $layananSummary);

        return Inertia::render('Reports/Show', [
            'report' => [
                'id'           => $report->id,
                'year'         => $report->year,
                'month'        => $report->month,
                'status'       => $report->status,
                'finalized_at' => $report->finalized_at?->format('d M Y, H:i'),
                'created_by'   => $report->creator?->name,
                'updated_by'   => $report->updater?->name,
                'created_at'   => $report->created_at?->format('d/m/Y H:i'),
                'updated_at'   => $report->updated_at?->format('d/m/Y H:i'),
            ],
            'kunjunganSummary' => $kunjunganSummary,
            'layananSummary'   => $layananSummary,
            'warnings'         => $warnings,
            'canFinalize'      => Gate::allows('finalize', $report),
            'canReopen'        => Gate::allows('reopen', $report),
        ]);
    }

    public function finalize(Request $request, MonthlyReport $report): RedirectResponse
    {
        Gate::authorize('finalize', $report);

        $report->update([
            'status'       => 'final',
            'finalized_at' => now(),
            'updated_by'   => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Laporan berhasil difinalisasi dan dikunci.');
    }

    public function reopen(Request $request, MonthlyReport $report): RedirectResponse
    {
        Gate::authorize('reopen', $report);

        $report->update([
            'status'       => 'draft',
            'finalized_at' => null,
            'updated_by'   => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Kunci laporan berhasil dibuka kembali oleh Administrator.');
    }
}
