import React from 'react';
import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
} from 'recharts';
import { formatNumber } from '@/lib/format';

function CustomTooltip({ active, payload, label }) {
    if (active && payload && payload.length) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white/95 backdrop-blur-sm p-3.5 shadow-lg text-xs min-w-[160px]">
                <p className="font-bold text-slate-800 text-sm mb-2 pb-1.5 border-b border-slate-100">
                    Bulan {label}
                </p>
                <div className="space-y-1.5">
                    {payload.map((entry, index) => (
                        <div key={`tooltip-${index}`} className="flex items-center justify-between gap-4">
                            <span className="flex items-center gap-1.5 text-slate-600">
                                <span
                                    className="h-2.5 w-2.5 rounded-full flex-shrink-0"
                                    style={{ backgroundColor: entry.color }}
                                />
                                {entry.name}
                            </span>
                            <span className="font-bold text-slate-900 tabular-nums">
                                {entry.value !== null && entry.value !== undefined
                                    ? formatNumber(entry.value)
                                    : '—'}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        );
    }
    return null;
}

export default function ChartTrend({ data = [], height = 260 }) {
    const hasAnyData = data.some((d) => d.total !== null);

    return (
        <div className="w-full">
            {!hasAnyData ? (
                <div className="flex flex-col items-center justify-center py-12 text-center text-slate-400 min-h-[220px]">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="h-10 w-10 mb-2 text-slate-300">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                    <p className="text-sm font-medium text-slate-600">Belum ada data kunjungan untuk tahun ini</p>
                    <p className="text-xs text-slate-400 mt-1">Data akan muncul otomatis setelah laporan diisi</p>
                </div>
            ) : (
                <div className="h-[240px] sm:h-[300px] w-full">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data} margin={{ top: 10, right: 10, left: -15, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
                            <XAxis
                                dataKey="name"
                                tick={{ fill: '#64748B', fontSize: 12 }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                            />
                            <YAxis
                                tick={{ fill: '#64748B', fontSize: 12 }}
                                tickLine={false}
                                axisLine={{ stroke: '#E2E8F0' }}
                                allowDecimals={false}
                            />
                            <Tooltip content={<CustomTooltip />} />
                            <Legend
                                verticalAlign="top"
                                align="right"
                                iconType="circle"
                                wrapperStyle={{ paddingBottom: '12px', fontSize: '12px' }}
                            />
                            {/* Total: #0F766E (Teal primer) */}
                            <Line
                                type="monotone"
                                dataKey="total"
                                name="Total"
                                stroke="#0F766E"
                                strokeWidth={2.5}
                                dot={{ r: 3.5, fill: '#0F766E' }}
                                activeDot={{ r: 5 }}
                                connectNulls={false}
                            />
                            {/* Dalam Gedung: #0EA5E9 (Sky) */}
                            <Line
                                type="monotone"
                                dataKey="in"
                                name="Dalam Gedung"
                                stroke="#0EA5E9"
                                strokeWidth={2}
                                dot={{ r: 3, fill: '#0EA5E9' }}
                                activeDot={{ r: 4.5 }}
                                connectNulls={false}
                            />
                            {/* Luar Gedung: #F59E0B (Amber) */}
                            <Line
                                type="monotone"
                                dataKey="out"
                                name="Luar Gedung"
                                stroke="#F59E0B"
                                strokeWidth={2}
                                dot={{ r: 3, fill: '#F59E0B' }}
                                activeDot={{ r: 4.5 }}
                                connectNulls={false}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}

