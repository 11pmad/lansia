import React, { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head, router } from '@inertiajs/react';

export default function Export({
    selectedYear,
    availableYears = [],
    months = [],
}) {
    const [year, setYear] = useState(selectedYear);
    const [month, setMonth] = useState('');
    const [isDownloading, setIsDownloading] = useState(false);

    const handleYearChange = (e) => {
        const newYear = Number(e.target.value);
        setYear(newYear);
        router.get(
            route('export.index'),
            { year: newYear },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleDownload = () => {
        setIsDownloading(true);

        const params = new URLSearchParams({ year });
        if (month) {
            params.append('month', month);
        }

        const url = `${route('export.download')}?${params.toString()}`;

        // Trigger direct browser download
        window.location.href = url;

        setTimeout(() => {
            setIsDownloading(false);
        }, 2000);
    };

    const selectedMonthName = month
        ? months.find((m) => m.month === Number(month))?.name
        : 'Semua Bulan';

    const existingReportsCount = months.filter((m) => m.has_report).length;

    return (
        <AuthenticatedLayout header={lang.export.title}>
            <Head title={lang.export.title} />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header Card */}
                <div className="rounded-card border border-teal-200 bg-gradient-to-br from-teal-50/70 to-white p-5 sm:p-6 shadow-sm">
                    <div className="flex items-start justify-between">
                        <div>
                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800 text-xs font-bold mb-2">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3.5 w-3.5">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                                {lang.export.summaryBadge}
                            </span>
                            <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                {lang.export.title}
                            </h1>
                            <p className="text-xs sm:text-sm text-slate-600 mt-1">
                                {lang.export.subtitle}
                            </p>
                        </div>
                    </div>

                    <div className="mt-4 pt-3 border-t border-teal-100 text-xs text-slate-600 space-y-1">
                        <p>• Berkas Excel memuat 2 sheet utama: <strong>Lap. Kunjungan</strong> &amp; <strong>Lap. Layanan Lansia</strong>.</p>
                        <p>• Seluruh rumus baku Dinkes (SPM, Komdat, persentase %) tetap aktif dan dihitung otomatis oleh Excel.</p>
                    </div>
                </div>

                {/* Filter Form Card */}
                <div className="rounded-card border border-slate-200 bg-white p-5 sm:p-6 shadow-sm space-y-5">
                    <h2 className="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                        Pilih Periode Laporan
                    </h2>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {/* Pilihan Tahun */}
                        <div>
                            <label htmlFor="export-year" className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                {lang.export.selectYear}
                            </label>
                            <select
                                id="export-year"
                                value={year}
                                onChange={handleYearChange}
                                className="w-full min-h-[48px] rounded-xl border border-slate-300 bg-white py-2.5 px-3.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600"
                            >
                                {availableYears.map((y) => (
                                    <option key={y} value={y}>
                                        Tahun {y}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Pilihan Bulan */}
                        <div>
                            <label htmlFor="export-month" className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                {lang.export.selectMonth}
                            </label>
                            <select
                                id="export-month"
                                value={month}
                                onChange={(e) => setMonth(e.target.value)}
                                className="w-full min-h-[48px] rounded-xl border border-slate-300 bg-white py-2.5 px-3.5 text-sm font-semibold text-slate-800 shadow-sm focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600"
                            >
                                <option value="">{lang.export.allMonthsOption}</option>
                                {months.map((m) => (
                                    <option key={m.month} value={m.month}>
                                        Bulan {m.name} {m.has_report ? `(${m.status === 'final' ? 'Final' : 'Draft'})` : '(Kosong)'}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Ringkasan Berkas yang Akan Diunduh */}
                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-2">
                        <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                            Rincian Berkas Ekspor
                        </span>
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs sm:text-sm text-slate-700 gap-1">
                            <span>Nama Berkas: <strong className="font-mono text-teal-900">
                                Laporan_Lansia_Payakumbuh_{year}_{month ? selectedMonthName.toUpperCase() : 'SEMUA'}.xlsx
                            </strong></span>
                            <span>Ketersediaan Data: <strong>{existingReportsCount} dari 12 Bulan</strong></span>
                        </div>
                    </div>

                    {/* Tombol Unduh Ekspor (Touch Target >= 56px) */}
                    <div className="pt-2">
                        <button
                            type="button"
                            onClick={handleDownload}
                            disabled={isDownloading}
                            className="w-full flex items-center justify-center gap-3 min-h-[56px] rounded-xl bg-teal-700 px-6 py-4 text-base font-bold text-white shadow-sm hover:bg-teal-800 active:bg-teal-900 transition focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 disabled:opacity-75 disabled:cursor-not-allowed"
                        >
                            {isDownloading ? (
                                <>
                                    <svg className="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24" fill="none">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    <span>{lang.export.downloading}</span>
                                </>
                            ) : (
                                <>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" className="h-6 w-6">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    <span>{lang.export.downloadButton}</span>
                                </>
                            )}
                        </button>
                    </div>

                    <p className="text-center text-xs text-slate-400">
                        {lang.export.templateNotice}
                    </p>
                </div>

                {/* Status Ketersediaan 12 Bulan */}
                <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                    <h3 className="text-sm font-bold text-slate-800">
                        Status Laporan Tahun {year}
                    </h3>

                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5">
                        {months.map((m) => (
                            <div
                                key={m.month}
                                className={`rounded-lg border p-2.5 text-xs flex items-center justify-between ${
                                    m.has_report
                                        ? m.status === 'final'
                                            ? 'border-emerald-200 bg-emerald-50/50 text-emerald-900'
                                            : 'border-amber-200 bg-amber-50/50 text-amber-900'
                                        : 'border-slate-200 bg-slate-50/50 text-slate-500'
                                }`}
                            >
                                <span className="font-semibold">{m.name}</span>
                                <span className="text-[10px] font-bold uppercase tracking-wider">
                                    {m.has_report ? (m.status === 'final' ? 'Final' : 'Draft') : 'Kosong'}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
