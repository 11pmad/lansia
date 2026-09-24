import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { formatNumber, formatPercentage } from '@/lib/format';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    Tooltip,
} from 'recharts';

function MiniTooltip({ active, payload, label }) {
    if (active && payload && payload.length) {
        return (
            <div className="rounded-lg border border-slate-200 bg-white/95 backdrop-blur-sm p-2 shadow-md text-xs">
                <span className="font-semibold text-slate-700">{label}: </span>
                <span className="font-bold text-teal-800 tabular-nums">
                    {payload[0].value !== null ? `${formatNumber(payload[0].value)} kunjungan` : '—'}
                </span>
            </div>
        );
    }
    return null;
}

export default function Dashboard({ dashboardData = {} }) {
    const user = usePage().props.auth.user;

    const currentYear = dashboardData.year || new Date().getFullYear();
    const currentMonthName = dashboardData.month_name || 'Bulan Berjalan';
    const reportStatus = dashboardData.report_status || 'empty';
    const filledCount = dashboardData.filled_kelurahans || 0;
    const totalCount = dashboardData.total_kelurahans || 6;
    const miniTrend = dashboardData.mini_trend || [];

    const hasMiniData = miniTrend.some((d) => d.total !== null);

    return (
        <AuthenticatedLayout header={lang.nav.home}>
            <Head title={lang.nav.home} />

            <div className="space-y-5">
                {/* Sapaan Petugas */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            {lang.dashboard.greeting}, {user?.name}
                        </h1>
                        <p className="text-sm text-slate-500">
                            Puskesmas Payolansek · {currentMonthName} {currentYear}
                        </p>
                    </div>
                    <span className="self-start sm:self-auto inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-50 text-teal-800 text-xs font-semibold border border-teal-200">
                        <span className="h-2 w-2 rounded-full bg-teal-600 animate-pulse" />
                        Sistem Aktif
                    </span>
                </div>

                {/* Kartu Status Laporan Bulan Berjalan */}
                <div className="rounded-card border border-teal-200 bg-gradient-to-br from-teal-50/80 to-white p-5 shadow-sm">
                    <div className="flex items-start justify-between">
                        <div>
                            <span className="text-xs font-bold text-teal-800 uppercase tracking-wider">
                                {lang.dashboard.currentReport}
                            </span>
                            <h2 className="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">
                                {currentMonthName} {currentYear}
                            </h2>
                        </div>
                        {reportStatus === 'final' && (
                            <span className="inline-flex items-center rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-300">
                                {lang.dashboard.final}
                            </span>
                        )}
                        {reportStatus === 'draft' && (
                            <span className="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-300">
                                {lang.dashboard.draft} · {filledCount}/{totalCount} {lang.dashboard.kelurahanFilled}
                            </span>
                        )}
                        {reportStatus === 'empty' && (
                            <span className="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700 border border-slate-300">
                                {lang.dashboard.notCreated}
                            </span>
                        )}
                    </div>

                    <div className="mt-4 pt-3 border-t border-teal-100 flex items-center justify-between text-xs sm:text-sm text-slate-600">
                        <span>Cakupan Wilayah: <strong>{totalCount} Kelurahan</strong></span>
                        <Link
                            href={dashboardData.report_id ? route('reports.show', dashboardData.report_id) : route('reports.index')}
                            className="text-teal-700 font-semibold hover:underline inline-flex items-center gap-1"
                        >
                            {dashboardData.report_id ? 'Buka Laporan Ini' : 'Daftar Laporan'}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </Link>
                    </div>
                </div>

                {/* Dua Tombol Aksi Utama (Mobile-First >= 48px) */}
                <div>
                    <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2.5">
                        {lang.dashboard.quickActions}
                    </h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <Link
                            href={route('scan.create')}
                            className="flex items-center justify-center gap-3 min-h-[56px] rounded-xl bg-teal-700 px-5 py-3.5 text-base font-bold text-white shadow-sm hover:bg-teal-800 active:bg-teal-900 transition focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" className="h-6 w-6">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                <circle cx="12" cy="13" r="4" />
                            </svg>
                            {lang.dashboard.scanReport}
                        </Link>

                        <Link
                            href={dashboardData.report_id ? route('forms.kunjungan.edit', dashboardData.report_id) : route('reports.index')}
                            className="flex items-center justify-center gap-3 min-h-[56px] rounded-xl border-2 border-teal-700 bg-white px-5 py-3.5 text-base font-bold text-teal-700 hover:bg-teal-50 active:bg-teal-100 transition focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" className="h-6 w-6">
                                <path d="M12 20h9" />
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" />
                            </svg>
                            {lang.dashboard.manualInput}
                        </Link>
                    </div>
                </div>

                {/* Kartu Capaian SPM & Kartu Tren Mini */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {/* Kartu SPM Bulan Ini */}
                    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    {lang.dashboard.spmCoverage}
                                </span>
                                <span className="text-xs font-semibold text-teal-700">{currentMonthName} {currentYear}</span>
                            </div>
                            <div className="mt-3 flex items-baseline gap-2">
                                <span className="text-3xl sm:text-4xl font-extrabold text-slate-900 tabular-nums">
                                    {formatPercentage(dashboardData.current_spm_pct, true, '— %')}
                                </span>
                                {dashboardData.current_spm_total !== null && (
                                    <span className="text-sm font-semibold text-slate-600">
                                        ({formatNumber(dashboardData.current_spm_total)} lansia)
                                    </span>
                                )}
                            </div>
                            <p className="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2">
                                Persentase lansia usia &gt;60 tahun yang dilayani sesuai standar SPM pada bulan ini.
                            </p>
                        </div>

                        <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Target Nasional: <strong>100%</strong></span>
                            <Link href={route('stats.index')} className="text-teal-700 font-semibold hover:underline">
                                Lihat Grafik SPM →
                            </Link>
                        </div>
                    </div>

                    {/* Kartu Tren Mini Kunjungan */}
                    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between mb-2">
                            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                {lang.dashboard.visitTrends} ({currentYear})
                            </span>
                            <Link
                                href={route('stats.index')}
                                className="text-xs font-semibold text-teal-700 hover:underline"
                            >
                                Selengkapnya →
                            </Link>
                        </div>

                        {!hasMiniData ? (
                            <div className="flex flex-col items-center justify-center py-6 text-center text-slate-400">
                                <p className="text-xs text-slate-500">Belum ada data kunjungan tahun {currentYear}</p>
                            </div>
                        ) : (
                            <div className="h-[130px] w-full pt-1">
                                <ResponsiveContainer width="100%" height="100%">
                                    <LineChart data={miniTrend} margin={{ top: 5, right: 10, left: 10, bottom: 0 }}>
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fill: '#94A3B8', fontSize: 10 }}
                                            tickLine={false}
                                            axisLine={false}
                                        />
                                        <Tooltip content={<MiniTooltip />} />
                                        <Line
                                            type="monotone"
                                            dataKey="total"
                                            stroke="#0F766E"
                                            strokeWidth={2}
                                            dot={{ r: 2.5, fill: '#0F766E' }}
                                            activeDot={{ r: 4 }}
                                            connectNulls={false}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                            </div>
                        )}

                        <p className="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2 flex items-center justify-between">
                            <span>Total kunjungan bulanan</span>
                            <span className="font-semibold text-slate-700">12 Bulan</span>
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
