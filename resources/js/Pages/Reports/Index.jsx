import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber, formatPercentage, getMonthName } from '@/lib/format';
import { lang } from '@/lang';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function ReportsIndex({ selectedYear, availableYears, monthsData }) {
    const [year, setYear] = useState(selectedYear);
    const [creatingMonth, setCreatingMonth] = useState(null);

    const handleYearChange = (newYear) => {
        setYear(newYear);
        router.get(route('reports.index'), { year: newYear }, { preserveState: true });
    };

    const handleCreateReport = (month) => {
        setCreatingMonth(month);
        router.post(
            route('reports.store'),
            { year, month },
            {
                onFinish: () => setCreatingMonth(null),
            }
        );
    };

    return (
        <AuthenticatedLayout header={lang.nav.reports}>
            <Head title="Daftar Laporan Bulanan" />

            <div className="space-y-6">
                {/* Header & Filter Tahun */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Laporan Bulanan Lansia
                        </h1>
                        <p className="text-sm text-slate-500">
                            Puskesmas Payolansek · Format Dinas Kesehatan
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('export.index', { year })}
                            className="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-teal-600 bg-teal-50 px-3.5 py-2 text-sm font-bold text-teal-700 hover:bg-teal-100 transition"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Ekspor Excel
                        </Link>

                        <label htmlFor="year-select" className="text-sm font-semibold text-slate-700">
                            Tahun:
                        </label>
                        <select
                            id="year-select"
                            value={year}
                            onChange={(e) => handleYearChange(Number(e.target.value))}
                            className="min-h-[44px] rounded-lg border-slate-300 bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm focus:border-teal-600 focus:ring-teal-600"
                        >
                            {availableYears.map((y) => (
                                <option key={y} value={y}>
                                    {y}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Grid 12 Kartu Bulan */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {monthsData.map((item) => {
                        const monthName = getMonthName(item.month);
                        const isDraft = item.status === 'draft';
                        const isFinal = item.status === 'final';
                        const isEmpty = item.status === 'empty';

                        return (
                            <div
                                key={item.month}
                                className={`rounded-card border bg-white p-5 shadow-sm transition flex flex-col justify-between ${
                                    isFinal
                                        ? 'border-emerald-200 bg-emerald-50/20'
                                        : isDraft
                                        ? 'border-teal-200 ring-1 ring-teal-600/10'
                                        : 'border-slate-200'
                                }`}
                            >
                                <div>
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <span className="text-xs font-bold text-slate-400">
                                                Bulan {String(item.month).padStart(2, '0')}
                                            </span>
                                            <h2 className="text-lg font-bold text-slate-900">
                                                {monthName} {year}
                                            </h2>
                                        </div>

                                        {isFinal && (
                                            <span className="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-200">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3 w-3">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                                </svg>
                                                Final
                                            </span>
                                        )}

                                        {isDraft && (
                                            <span className="inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 border border-amber-200">
                                                Draft
                                            </span>
                                        )}

                                        {isEmpty && (
                                            <span className="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                                Belum Ada
                                            </span>
                                        )}
                                    </div>

                                    {!isEmpty ? (
                                        <div className="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
                                            <div className="flex justify-between items-center">
                                                <span>Pengisian Kelurahan:</span>
                                                <span className="font-semibold text-slate-900">
                                                    {item.filled_count} / {item.total_kelurahan} Kelurahan
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Capaian SPM (&gt;60 th):</span>
                                                <span className="font-bold text-teal-800 tabular-nums">
                                                    {formatPercentage(item.spm_pct)} ({item.spm_total} org)
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span>Total Kunjungan:</span>
                                                <span className="font-semibold text-slate-900 tabular-nums">
                                                    {formatNumber(item.total_visit)} kunjungan
                                                </span>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="mt-4 text-xs text-slate-400 border-t border-slate-100 pt-3">
                                            Belum ada laporan yang dibuat untuk bulan ini.
                                        </p>
                                    )}
                                </div>

                                <div className="mt-5 pt-3">
                                    {!isEmpty ? (
                                        <Link
                                            href={route('reports.show', item.report_id)}
                                            className="flex w-full min-h-[44px] items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-800 active:bg-teal-900 transition focus:outline-none focus:ring-2 focus:ring-teal-600"
                                        >
                                            Buka Laporan
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4">
                                                <path d="m9 18 6-6-6-6" />
                                            </svg>
                                        </Link>
                                    ) : (
                                        <button
                                            type="button"
                                            disabled={creatingMonth === item.month}
                                            onClick={() => handleCreateReport(item.month)}
                                            className="flex w-full min-h-[44px] items-center justify-center gap-2 rounded-lg border-2 border-dashed border-teal-600 bg-teal-50/50 px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-teal-50 active:bg-teal-100 transition disabled:opacity-50"
                                        >
                                            {creatingMonth === item.month ? (
                                                'Menyiapkan...'
                                            ) : (
                                                <>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4">
                                                        <line x1="12" y1="5" x2="12" y2="19" />
                                                        <line x1="5" y1="12" x2="19" y2="12" />
                                                    </svg>
                                                    Buat Laporan
                                                </>
                                            )}
                                        </button>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
