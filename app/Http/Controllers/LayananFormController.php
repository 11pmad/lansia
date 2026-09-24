<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLayananRequest;
use App\Models\Kelurahan;
use App\Models\LayananRow;
use App\Models\MonthlyReport;
use App\Services\LansiaCalculator;
use App\Support\LansiaFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LayananFormController extends Controller
{
    public function edit(MonthlyReport $report, Request $request): Response
    {
        $kelurahans = Kelurahan::where('puskesmas_id', $report->puskesmas_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeKelurahanId = (int) $request->input('kelurahan', $kelurahans->first()?->id);

        $layananRows = LayananRow::where('monthly_report_id', $report->id)
            ->get()
            ->keyBy('kelurahan_id');

        // Skema dinamis dari LansiaFields (Aturan 04_AGENT_RULES.md)
        $fieldSchema = [
            'sasaran' => collect(LansiaFields::AGES)->flatMap(function ($age) {
                return collect(LansiaFields::GENDERS)->map(function ($sex) use ($age) {
                    return [
                        'key'      => "sas_{$age}_{$sex}",
                        'age'      => $age,
                        'ageLabel' => LansiaFields::AGE_LABELS[$age],
                        'sex'      => $sex,
                        'sexLabel' => LansiaFields::GENDER_LABELS[$sex],
                    ];
                });
            })->values(),

            'kelainan' => collect(LansiaFields::KELAINAN_KEYS)->map(function ($label, $key) {
                return [
                    'key'   => $key,
                    'label' => $label,
                    'lKey'  => "kel_{$key}_l",
                    'pKey'  => "kel_{$key}_p",
                ];
            })->values(),

            'tindakan' => collect(LansiaFields::LAYANAN_TINDAKAN)->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $label,
            ])->values(),

            'lain' => collect(LansiaFields::LAYANAN_LAIN)->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $label,
                'type'  => $key === 'keterangan' ? 'text' : 'number',
            ])->values(),
        ];

        $rowsData = [];
        $calculationsMap = [];
        $completionMap = [];

        foreach ($kelurahans as $kel) {
            $row = $layananRows->get($kel->id);
            $rowData = $row ? $row->only(LansiaFields::allLayananRowColumns()) : [];
            $rowsData[$kel->id] = $rowData;

            $calc = LansiaCalculator::calculateLayananRow($rowData);
            $calculationsMap[$kel->id] = $calc;

            $completionMap[$kel->id] = ($calc['kelainan_total'] ?? 0) > 0 || ($calc['tindakan_total'] ?? 0) > 0;
        }

        return Inertia::render('Forms/Layanan', [
            'report' => [
                'id'     => $report->id,
                'year'   => $report->year,
                'month'  => $report->month,
                'status' => $report->status,
            ],
            'kelurahans'         => $kelurahans,
            'activeKelurahanId'  => $activeKelurahanId,
            'initialRows'        => $rowsData,
            'initialCalcs'       => $calculationsMap,
            'completionMap'      => $completionMap,
            'fieldSchema'        => $fieldSchema,
        ]);
    }

    public function update(
        SaveLayananRequest $request,
        MonthlyReport $report,
        Kelurahan $kelurahan
    ) {
        if ($report->isFinal()) {
            abort(403, 'Laporan telah difinalisasi dan terkunci untuk pengeditan.');
        }

        $validated = $request->validated();

        $row = LayananRow::updateOrCreate(
            [
                'monthly_report_id' => $report->id,
                'kelurahan_id'      => $kelurahan->id,
            ],
            $validated
        );

        $report->update(['updated_by' => $request->user()->id]);

        $calculated = LansiaCalculator::calculateLayananRow($row);

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'row'          => $row->only(LansiaFields::allLayananRowColumns()),
                'calculations' => $calculated,
                'saved_at'     => now()->format('H:i'),
            ]);
        }

        return redirect()->back()->with('success', "Data Layanan {$kelurahan->name} berhasil disimpan.");
    }
}
