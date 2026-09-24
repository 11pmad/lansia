import React from 'react';
import {
    ResponsiveContainer,
    ComposedChart,
    Bar,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
} from 'recharts';
import { formatNumber, formatPercentage } from '@/lib/format';

function CustomTooltip({ active, payload, label }) {
    if (active && payload && payload.length) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white/95 backdrop-blur-sm p-3.5 shadow-lg text-xs min-w-[180px]">
                <p className="font-bold text-slate-800 text-sm mb-2 pb-1.5 border-b border-slate-100">
                    Bulan {label}
                </p>
                <div className="space-y-1.5">
                    {payload.map((entry, index) => {
                        const isPercentage = entry.dataKey === 'pct';
                        const displayVal = entry.value !== null && entry.value !== undefined
                            ? (isPercentage ? formatPercentage(entry.value) : formatNumber(entry.value) + ' org')
                            : '—';

                        return (
                            <div key={`tooltip-${index}`} className="flex items-center justify-between gap-4">
                                <span className="flex items-center gap-1.5 text-slate-600">
                                    <span
                                        className="h-2.5 w-2.5 rounded-full flex-shrink-0"
                                        style={{ backgroundColor: entry.color }}
                                    />
                                    {entry.name}
                                </span>
                                <span className="font-bold text-slate-900 tabular-nums">
                                    {displayVal}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    }
    return null;
}

export default function ChartSpm({ data = [], summary = {} }) {
    const hasAnyData = data.some((d) => d.total !== null);

    return (
        <div className="w-full space-y-4">
            {/* Kartu Ringkas TOTAL SPM */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-3 sm:p-4">
                    <span className="text-xs font-semibold text-blue-700">Laki-laki (L Abs)</span>
                    <p className="mt-1 text-xl sm:text-2xl font-black text-blue-900 tabular-nums">
                        {formatNumber(data.reduce((acc, curr) => acc + (curr.l_abs || 0), 0))}
                    </p>
                    <span className="text-[11px] text-blue-600">Total Akumulasi</span>
                </div>

                <div className="rounded-xl border border-pink-100 bg-pink-50/50 p-3 sm:p-4">
                    <span className="text-xs font-semibold text-pink-700">Perempuan (P Abs)</span>
                    <p className="mt-1 text-xl sm:text-2xl font-black text-pink-900 tabular-nums">
                        {formatNumber(data.reduce((acc, curr) => acc + (curr.p_abs || 0), 0))}
                    </p>
                    <span className="text-[11px] text-pink-600">Total Akumulasi</span>
                </div>

                <div className="rounded-xl border border-teal-200 bg-teal-50/60 p-3 sm:p-4">
                    <span className="text-xs font-semibold text-teal-800">TOTAL Dilayani</span>
                    <p className="mt-1 text-xl sm:text-2xl font-black text-teal-950 tabular-nums">
                        {formatNumber(summary?.total_spm_ytd || 0)}
                    </p>
                    <span className="text-[11px] text-teal-700">Lansia &gt;60 th</span>
                </div>

                <div className="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <span className="text-xs font-semibold text-slate-700">Capaian Terakhir</span>
                    <p className="mt-1 text-xl sm:text-2xl font-black text-slate-900 tabular-nums">
                        {formatPercentage(summary?.latest_spm_pct, true, '—')}
                    </p>
                    <span className="text-[11px] text-slate-500">
                        {summary?.latest_month_name || 'Belum ada data'}
                    </span>
                </div>
            </div>

            {/* Grafik ComposedChart */}
            {!hasAnyData ? (
                <div className="flex flex-col items-center justify-center py-12 text-center text-slate-400 min-h-[220px]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="h-10 w-10 mb-2 text-slate-300">
                        <path d="M12 20V10" />
                        <path d="M18 20V4" />
                        <path d="M6 20v-4" />
                    </svg>
                    <p className="text-sm font-medium text-slate-600">Belum ada data capaian SPM untuk tahun ini</p>
                    <p className="text-xs text-slate-400 mt-1">Data akan muncul otomatis setelah kunjungan lansia diisi</p>
                </div>
            ) : (
                <div className="h-[260px] sm:h-[320px] w-full pt-2">
                    <ResponsiveContainer width="100%" height="100%">
                        <ComposedChart data={data} margin={{ top: 10, right: 10, left: -15, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
                            <XAxis
                                dataKey="name"
                                tick={{ fill: '#64748B', fontSize: 12 }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                            />
                            {/* Left Y-Axis: Jumlah Lansia */}
                            <YAxis
                                yAxisId="left"
                                tick={{ fill: '#64748B', fontSize: 12 }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                                allowDecimals={false}
                            />
                            {/* Right Y-Axis: Persentase Capaian SPM */}
                            <YAxis
                                yAxisId="right"
                                orientation="right"
                                tick={{ fill: '#0F172A', fontSize: 12, fontWeight: 600 }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                                unit="%"
                                domain={[0, 'auto']}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend
                                verticalAlign="top"
                                align="right"
                                iconType="circle"
                                wrapperStyle={{ paddingBottom: '12px', fontSize: '12px' }}
                            />
                            {/* Batang L Abs: #2563EB */}
                            <Bar
                                yAxisId="left"
                                dataKey="l_abs"
                                name="Laki-laki (Abs)"
                                fill="#2563EB"
                                radius={[4, 4, 0, 0]}
                                maxBarSize={28}
                            />
                            {/* Batang P Abs: #DB2777 */}
                            <Bar
                                yAxisId="left"
                                dataKey="p_abs"
                                name="Perempuan (Abs)"
                                fill="#DB2777"
                                radius={[4, 4, 0, 0]}
                                maxBarSize={28}
                            />
                            {/* Garis %: #0F172A */}
                            <Line
                                yAxisId="right"
                                type="monotone"
                                dataKey="pct"
                                name="Capaian SPM (%)"
                                stroke="#0F172A"
                                strokeWidth={2.5}
                                dot={{ r: 3.5, fill: '#0F172A' }}
                                activeDot={{ r: 5 }}
                                connectNulls={false}
                            />
                        </ComposedChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}

