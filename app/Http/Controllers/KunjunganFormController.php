<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveKunjunganRequest;
use App\Models\Kelurahan;
use App\Models\KunjunganRow;
use App\Models\MonthlyReport;
use App\Services\LansiaCalculator;
use App\Support\LansiaFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KunjunganFormController extends Controller
{
    public function edit(MonthlyReport $report, Request $request): Response
    {
        $kelurahans = Kelurahan::where('puskesmas_id', $report->puskesmas_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $activeKelurahanId = (int) $request->input('kelurahan', $kelurahans->first()?->id);

        $kunjunganRows = KunjunganRow::where('monthly_report_id', $report->id)
            ->get()
            ->keyBy('kelurahan_id');

        // Bentuk skema dinamis dari LansiaFields (Aturan 04_AGENT_RULES.md)
        $fieldSchema = [
            'umum' => collect(LansiaFields::KUNJUNGAN_UMUM)->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $label,
            ])->values(),

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

            'dalam' => collect(LansiaFields::AGES)->flatMap(function ($age) {
                return collect(LansiaFields::GENDERS)->flatMap(function ($sex) use ($age) {
                    return collect(LansiaFields::VISIT_TYPES)->map(function ($type) use ($age, $sex) {
                        return [
                            'key'       => "in_{$age}_{$sex}_{$type}",
                            'age'       => $age,
                            'ageLabel'  => LansiaFields::AGE_LABELS[$age],
                            'sex'       => $sex,
                            'sexLabel'  => LansiaFields::GENDER_LABELS[$sex],
                            'type'      => $type,
                            'typeLabel' => LansiaFields::VISIT_TYPE_LABELS[$type],
                        ];
                    });
                });
            })->values(),

            'luar' => collect(LansiaFields::AGES)->flatMap(function ($age) {
                return collect(LansiaFields::GENDERS)->flatMap(function ($sex) use ($age) {
                    return collect(LansiaFields::VISIT_TYPES)->map(function ($type) use ($age, $sex) {
                        return [
                            'key'       => "out_{$age}_{$sex}_{$type}",
                            'age'       => $age,
                            'ageLabel'  => LansiaFields::AGE_LABELS[$age],
                            'sex'       => $sex,
                            'sexLabel'  => LansiaFields::GENDER_LABELS[$sex],
                            'type'      => $type,
                            'typeLabel' => LansiaFields::VISIT_TYPE_LABELS[$type],
                        ];
                    });
                });
            })->values(),

            'mandiri' => collect(LansiaFields::MANDIRI_COLUMNS)->map(fn ($label, $key) => [
                'key'   => $key,
                'label' => $label,
            ])->values(),
        ];

        // Format baris untuk React
        $rowsData = [];
        $calculationsMap = [];
        $completionMap = [];

        foreach ($kelurahans as $kel) {
            $row = $kunjunganRows->get($kel->id);
            $rowData = $row ? $row->only(LansiaFields::allKunjunganRowColumns()) : [];
            $rowsData[$kel->id] = $rowData;

            $calc = LansiaCalculator::calculateKunjunganRow($rowData);
            $calculationsMap[$kel->id] = $calc;

            // Indikator terisi jika ada kunjungan > 0
            $completionMap[$kel->id] = ($calc['total_kunjungan_bulan'] ?? 0) > 0;
        }

        return Inertia::render('Forms/Kunjungan', [
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
        SaveKunjunganRequest $request,
        MonthlyReport $report,
        Kelurahan $kelurahan
    ) {
        if ($report->isFinal()) {
            abort(403, 'Laporan telah difinalisasi dan terkunci untuk pengeditan.');
        }

        $validated = $request->validated();

        $row = KunjunganRow::updateOrCreate(
            [
                'monthly_report_id' => $report->id,
                'kelurahan_id'      => $kelurahan->id,
            ],
            $validated
        );

        $report->update(['updated_by' => $request->user()->id]);

        $calculated = LansiaCalculator::calculateKunjunganRow($row);

        if ($request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'row'          => $row->only(LansiaFields::allKunjunganRowColumns()),
                'calculations' => $calculated,
                'saved_at'     => now()->format('H:i'),
            ]);
        }

        return redirect()->back()->with('success', "Data Kunjungan {$kelurahan->name} berhasil disimpan.");
    }
}
