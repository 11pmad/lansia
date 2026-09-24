import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ChartTrend from '@/Components/ChartTrend';
import ChartSpm from '@/Components/ChartSpm';
import StatCard from '@/Components/StatCard';
import { lang } from '@/lang';
import { formatNumber, formatPercentage } from '@/lib/format';
import { Head, router } from '@inertiajs/react';

export default function Statistik({
    year,
    kelurahanId,
    availableYears = [],
    kelurahans = [],
    trend = [],
    spm = [],
    summary = {},
}) {
    const handleYearChange = (e) => {
        const newYear = e.target.value;
        router.get(
            route('stats.index'),
            { year: newYear, kelurahan_id: kelurahanId || undefined },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleKelurahanChange = (e) => {
        const newKelurahanId = e.target.value;
        router.get(
            route('stats.index'),
            { year, kelurahan_id: newKelurahanId ? Number(newKelurahanId) : undefined },
            { preserveState: true, preserveScroll: true }
        );
    };

    const activeKelurahanName = kelurahanId
        ? kelurahans.find((k) => k.id === kelurahanId)?.name || ''
        : lang.stats.allKelurahans;

    return (
        <AuthenticatedLayout header={lang.nav.stats}>
            <Head title={lang.stats.title} />

            <div className="space-y-6">
                {/* Header & Filter Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {lang.stats.title}
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Wilayah: <strong>{activeKelurahanName}</strong> · Tahun {year}
                        </p>
                    </div>

                    {/* Filter Controls (Touch targets >= 48px) */}
                    <div className="flex items-center gap-2.5">
                        <div className="relative">
                            <label htmlFor="filter-year" className="sr-only">
                                {lang.stats.filterYear}
                            </label>
                            <select
                                id="filter-year"
                                value={year}
                                onChange={handleYearChange}
                                className="min-h-[48px] rounded-xl border border-slate-300 bg-white py-2.5 pl-3.5 pr-9 text-sm font-semibold text-slate-800 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600"
                            >
                                {availableYears.map((y) => (
                                    <option key={y} value={y}>
                                        Tahun {y}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="relative flex-1 sm:flex-none">
                            <label htmlFor="filter-kelurahan" className="sr-only">
                                {lang.stats.filterKelurahan}
                            </label>
                            <select
                                id="filter-kelurahan"
                                value={kelurahanId || ''}
                                onChange={handleKelurahanChange}
                                className="w-full sm:w-auto min-h-[48px] rounded-xl border border-slate-300 bg-white py-2.5 pl-3.5 pr-9 text-sm font-semibold text-slate-800 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600"
                            >
                                <option value="">{lang.stats.allKelurahans}</option>
                                {kelurahans.map((kel) => (
                                    <option key={kel.id} value={kel.id}>
                                        Kel. {kel.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                {/* 4 Kartu Metrik Utama */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <StatCard
                        title={lang.stats.totalAnnualVisits}
                        value={formatNumber(summary.total_visits_ytd)}
                        subtext="Kunjungan kumulatif (dalam + luar gedung)"
                        icon={
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        }
                    />
                    <StatCard
                        title={lang.stats.totalAnnualSpm}
                        value={formatNumber(summary.total_spm_ytd)}
                        subtext="Lansia >60 th dilayani standar"
                        icon={
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                <polyline points="22 4 12 14.01 9 11.01" />
                            </svg>
                        }
                    />
                    <StatCard
                        title={lang.stats.latestSpmPct}
                        value={formatPercentage(summary.latest_spm_pct, true, '—')}
                        subtext={summary.latest_month_name ? `Bulan ${summary.latest_month_name}` : 'Belum ada data'}
                        badge={summary.latest_spm_pct !== null ? 'Target 100%' : null}
                    />
                    <StatCard
                        title={lang.stats.targetLansia}
                        value={formatNumber(summary.target_lansia_60)}
                        subtext="Sasaran usia >60 tahun wilayah"
                        icon={
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                                <circle cx="12" cy="12" r="10" />
                                <circle cx="12" cy="12" r="6" />
                                <circle cx="12" cy="12" r="2" />
                            </svg>
                        }
                    />
                </div>

                {/* Grafik 1: Tren Kunjungan */}
                <div className="rounded-card border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
                        <div>
                            <h2 className="text-base sm:text-lg font-bold text-slate-900">
                                {lang.stats.visitTrendTitle}
                            </h2>
                            <p className="text-xs text-slate-500">
                                {lang.stats.visitTrendSubtitle}
                            </p>
                        </div>
                        <span className="self-start sm:self-auto text-[11px] font-medium text-slate-400">
                            {lang.stats.gapNotice}
                        </span>
                    </div>

                    <ChartTrend data={trend} />
                </div>

                {/* Grafik 2: Capaian SPM Lansia */}
                <div className="rounded-card border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
                        <div>
                            <h2 className="text-base sm:text-lg font-bold text-slate-900">
                                {lang.stats.spmChartTitle}
                            </h2>
                            <p className="text-xs text-slate-500">
                                {lang.stats.spmChartSubtitle}
                            </p>
                        </div>
                        <span className="self-start sm:self-auto text-[11px] font-medium text-slate-400">
                            Batang: L/P Abs · Garis: % Capaian
                        </span>
                    </div>

                    <ChartSpm data={spm} summary={summary} />
                </div>

                {/* Tabel Data Alternatif (Aksesibilitas & Detail Angka) */}
                <div className="rounded-card border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div className="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h2 className="text-base font-bold text-slate-900">
                                {lang.stats.tableTitle}
                            </h2>
                            <p className="text-xs text-slate-500">
                                {lang.stats.tableSubtitle}
                            </p>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs sm:text-sm border-collapse">
                            <thead>
                                <tr className="border-b border-slate-200 bg-slate-50/80 text-slate-700 font-bold">
                                    <th className="sticky left-0 bg-slate-50/95 backdrop-blur-sm z-10 py-3 pl-4 pr-3 min-w-[110px] shadow-[1px_0_0_0_#E2E8F0]">
                                        {lang.stats.month}
                                    </th>
                                    <th className="py-3 px-3 text-right">{lang.stats.totalVisits}</th>
                                    <th className="py-3 px-3 text-right">{lang.stats.inBuilding}</th>
                                    <th className="py-3 px-3 text-right">{lang.stats.outBuilding}</th>
                                    <th className="py-3 px-3 text-right text-blue-800">{lang.stats.maleAbs}</th>
                                    <th className="py-3 px-3 text-right text-pink-800">{lang.stats.femaleAbs}</th>
                                    <th className="py-3 px-3 text-right text-teal-900">{lang.stats.spmTotal}</th>
                                    <th className="py-3 pr-4 pl-3 text-right font-black text-slate-900">{lang.stats.spmPct}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 font-medium">
                                {trend.map((t, idx) => {
                                    const s = spm[idx] || {};
                                    const isRowEmpty = t.total === null;

                                    return (
                                        <tr
                                            key={t.month}
                                            className={`hover:bg-slate-50/60 transition ${
                                                isRowEmpty ? 'text-slate-400' : 'text-slate-800'
                                            }`}
                                        >
                                            <td className="sticky left-0 bg-white group-hover:bg-slate-50/60 z-10 py-2.5 pl-4 pr-3 font-semibold shadow-[1px_0_0_0_#E2E8F0]">
                                                {t.full_name}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums">
                                                {formatNumber(t.total)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                                {formatNumber(t.in)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums text-slate-600">
                                                {formatNumber(t.out)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums text-blue-700">
                                                {formatNumber(s.l_abs)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums text-pink-700">
                                                {formatNumber(s.p_abs)}
                                            </td>
                                            <td className="py-2.5 px-3 text-right tabular-nums font-bold text-teal-800">
                                                {formatNumber(s.total)}
                                            </td>
                                            <td className="py-2.5 pr-4 pl-3 text-right tabular-nums font-bold text-slate-900">
                                                {formatPercentage(s.pct, true, '—')}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                            <tfoot>
                                <tr className="border-t-2 border-slate-300 bg-slate-50 font-bold text-slate-900">
                                    <td className="sticky left-0 bg-slate-50 z-10 py-3 pl-4 pr-3 uppercase tracking-wider shadow-[1px_0_0_0_#E2E8F0]">
                                        TOTAL TAHUNAN
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums">
                                        {formatNumber(summary.total_visits_ytd)}
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums text-slate-600">
                                        {formatNumber(trend.reduce((acc, curr) => acc + (curr.in || 0), 0))}
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums text-slate-600">
                                        {formatNumber(trend.reduce((acc, curr) => acc + (curr.out || 0), 0))}
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums text-blue-800">
                                        {formatNumber(spm.reduce((acc, curr) => acc + (curr.l_abs || 0), 0))}
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums text-pink-800">
                                        {formatNumber(spm.reduce((acc, curr) => acc + (curr.p_abs || 0), 0))}
                                    </td>
                                    <td className="py-3 px-3 text-right tabular-nums text-teal-900">
                                        {formatNumber(summary.total_spm_ytd)}
                                    </td>
                                    <td className="py-3 pr-4 pl-3 text-right tabular-nums text-teal-800">
                                        —
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
