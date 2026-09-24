import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber, formatPercentage, getMonthName } from '@/lib/format';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function ReportsRecap({
    report,
    kelurahans = [],
    kunjunganData = [],
    layananData = [],
    kunjunganSummary = {},
    layananSummary = {},
    kelainanKeys = {},
}) {
    const [activeTab, setActiveTab] = useState('kunjungan'); // 'kunjungan' | 'layanan'
    const monthName = getMonthName(report.month);

    return (
        <AuthenticatedLayout
            header={`Rekapitulasi ${monthName} ${report.year}`}
            backUrl={route('reports.show', report.id)}
        >
            <Head title={`Rekapitulasi — ${monthName} ${report.year}`} />

            <div className="space-y-4">
                {/* Header Rekap */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-card border border-slate-200 shadow-sm">
                    <div>
                        <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Puskesmas Payolansek · Format Dinkes
                        </span>
                        <h1 className="text-lg sm:text-xl font-bold text-slate-900">
                            Rekapitulasi Laporan {monthName} {report.year}
                        </h1>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <a
                            href={route('export.download', { year: report.year, month: report.month })}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-teal-700 bg-teal-700 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-teal-800 transition"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3.5 w-3.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Ekspor Excel
                        </a>

                        {/* Tab Toggle */}
                        <div className="flex rounded-lg bg-slate-100 p-1 border border-slate-200">
                            <button
                                type="button"
                                onClick={() => setActiveTab('kunjungan')}
                                className={`px-3 py-1.5 text-xs font-bold rounded-md transition ${
                                    activeTab === 'kunjungan'
                                        ? 'bg-teal-700 text-white shadow-sm'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                Kunjungan & SPM
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('layanan')}
                                className={`px-3 py-1.5 text-xs font-bold rounded-md transition ${
                                    activeTab === 'layanan'
                                        ? 'bg-teal-700 text-white shadow-sm'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                Layanan & Kelainan
                            </button>
                        </div>
                    </div>
                </div>

                {/* Tabel Rekap Kunjungan */}
                {activeTab === 'kunjungan' && (
                    <div className="rounded-card border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="p-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center text-xs text-slate-600">
                            <span>Geser tabel ke samping untuk melihat seluruh kolom.</span>
                            <span className="font-bold text-teal-800">
                                Capaian SPM Total: {formatPercentage(kunjunganSummary?.spm_pct)}
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr className="bg-slate-100 text-slate-700 font-bold border-b border-slate-300">
                                        <th className="sticky left-0 bg-slate-100 px-3 py-2.5 z-10 border-r border-slate-200 min-w-[130px]">
                                            Kelurahan
                                        </th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Sasaran &gt;60</th>
                                        <th className="px-3 py-2.5 text-right min-w-[60px]">In (L)</th>
                                        <th className="px-3 py-2.5 text-right min-w-[60px]">In (P)</th>
                                        <th className="px-3 py-2.5 text-right min-w-[60px]">Out (L)</th>
                                        <th className="px-3 py-2.5 text-right min-w-[60px]">Out (P)</th>
                                        <th className="px-3 py-2.5 text-right min-w-[80px] bg-teal-50/50">SPM L Abs</th>
                                        <th className="px-3 py-2.5 text-right min-w-[80px] bg-teal-50/50">SPM P Abs</th>
                                        <th className="px-3 py-2.5 text-right min-w-[90px] bg-teal-100/60 font-extrabold text-teal-950">
                                            SPM Total
                                        </th>
                                        <th className="px-3 py-2.5 text-right min-w-[90px] bg-teal-700 text-white font-extrabold">
                                            Capaian %
                                        </th>
                                        <th className="px-3 py-2.5 text-right min-w-[80px]">Total Kunjungan</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Mandiri A</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Mandiri B</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Mandiri C</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {kunjunganData.map(({ kelurahan, raw, calculations }) => (
                                        <tr key={kelurahan.id} className="hover:bg-slate-50/80">
                                            <td className="sticky left-0 bg-white px-3 py-2.5 font-bold text-slate-900 border-r border-slate-200 z-10 whitespace-nowrap">
                                                {kelurahan.name}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-medium tabular-nums">
                                                {formatNumber(calculations.total_lansia_60)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {formatNumber(raw.in_a6069_l_baru || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {formatNumber(raw.in_a6069_p_baru || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {formatNumber(raw.out_a6069_l_baru || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {formatNumber(raw.out_a6069_p_baru || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-semibold tabular-nums bg-teal-50/40 text-blue-900">
                                                {formatNumber(calculations.spm_l_abs)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-semibold tabular-nums bg-teal-50/40 text-pink-900">
                                                {formatNumber(calculations.spm_p_abs)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-bold tabular-nums bg-teal-100/50 text-teal-950">
                                                {formatNumber(calculations.spm_total)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-extrabold tabular-nums bg-teal-50 text-teal-800">
                                                {formatPercentage(calculations.spm_pct)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-semibold tabular-nums">
                                                {formatNumber(calculations.total_kunjungan_bulan)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.mandiri_6069_a || 0) + (raw.mandiri_70_a || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.mandiri_6069_b || 0) + (raw.mandiri_70_b || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.mandiri_6069_c || 0) + (raw.mandiri_70_c || 0)}
                                            </td>
                                        </tr>
                                    ))}

                                    {/* Baris JUMLAH Sesuai Aturan docs/02_DATABASE.md */}
                                    <tr className="bg-slate-100 font-extrabold text-slate-900 border-t-2 border-slate-300">
                                        <td className="sticky left-0 bg-slate-100 px-3 py-3 border-r border-slate-300 z-10 uppercase tracking-wider">
                                            JUMLAH
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {formatNumber(kunjunganSummary.total_lansia_60)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums" colSpan={4}>
                                            —
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums bg-teal-100 text-blue-900">
                                            {formatNumber(kunjunganSummary.spm_l_abs)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums bg-teal-100 text-pink-900">
                                            {formatNumber(kunjunganSummary.spm_p_abs)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums bg-teal-200 text-teal-950 font-black">
                                            {formatNumber(kunjunganSummary.spm_total)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums bg-teal-800 text-white font-black text-sm">
                                            {formatPercentage(kunjunganSummary.spm_pct)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums font-black">
                                            {formatNumber(kunjunganSummary.total_kunjungan_bulan)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums" colSpan={3}>
                                            —
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                {/* Tabel Rekap Layanan */}
                {activeTab === 'layanan' && (
                    <div className="rounded-card border border-slate-200 bg-white shadow-sm overflow-hidden">
                        <div className="p-3 bg-slate-50 border-b border-slate-200 flex justify-between items-center text-xs text-slate-600">
                            <span>14 Macam Kelainan Kesehatan Lansia</span>
                            <span className="font-bold text-teal-800">
                                Total Temuan: {formatNumber(layananSummary?.kelainan_total)}
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr className="bg-slate-100 text-slate-700 font-bold border-b border-slate-300">
                                        <th className="sticky left-0 bg-slate-100 px-3 py-2.5 z-10 border-r border-slate-200 min-w-[130px]">
                                            Kelurahan
                                        </th>
                                        <th className="px-3 py-2.5 text-right min-w-[80px]">Total Kelainan L</th>
                                        <th className="px-3 py-2.5 text-right min-w-[80px]">Total Kelainan P</th>
                                        <th className="px-3 py-2.5 text-right min-w-[90px] bg-teal-100 font-bold text-teal-950">
                                            Total Kasus
                                        </th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">DM (Gula)</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">TD Tinggi</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Kolesterol</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Asam Urat</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Penglihatan</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Tindakan Obati</th>
                                        <th className="px-3 py-2.5 text-right min-w-[70px]">Konseling</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {layananData.map(({ kelurahan, raw, calculations }) => (
                                        <tr key={kelurahan.id} className="hover:bg-slate-50/80">
                                            <td className="sticky left-0 bg-white px-3 py-2.5 font-bold text-slate-900 border-r border-slate-200 z-10 whitespace-nowrap">
                                                {kelurahan.name}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-medium tabular-nums text-blue-900">
                                                {formatNumber(calculations.kelainan_total_l)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-medium tabular-nums text-pink-900">
                                                {formatNumber(calculations.kelainan_total_p)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right font-bold tabular-nums bg-teal-50 text-teal-900">
                                                {formatNumber(calculations.kelainan_total)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.kel_dm_l || 0) + (raw.kel_dm_p || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.kel_td_tinggi_l || 0) + (raw.kel_td_tinggi_p || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.kel_kolesterol_l || 0) + (raw.kel_kolesterol_p || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.kel_asam_urat_l || 0) + (raw.kel_asam_urat_p || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.kel_penglihatan_l || 0) + (raw.kel_penglihatan_p || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {formatNumber(raw.pengobatan_obati || 0)}
                                            </td>
                                            <td className="px-3 py-2.5 text-right tabular-nums text-slate-600">
                                                {(raw.konseling_baru || 0) + (raw.konseling_lama || 0)}
                                            </td>
                                        </tr>
                                    ))}

                                    <tr className="bg-slate-100 font-extrabold text-slate-900 border-t-2 border-slate-300">
                                        <td className="sticky left-0 bg-slate-100 px-3 py-3 border-r border-slate-300 z-10 uppercase tracking-wider">
                                            JUMLAH
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums text-blue-900">
                                            {formatNumber(layananSummary.kelainan_total_l)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums text-pink-900">
                                            {formatNumber(layananSummary.kelainan_total_p)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums bg-teal-200 text-teal-950 font-black">
                                            {formatNumber(layananSummary.kelainan_total)}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums" colSpan={7}>
                                            —
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
