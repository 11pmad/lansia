import React from 'react';

export default function StatCard({
    title,
    value,
    subtext,
    badge,
    icon,
    className = '',
}) {
    return (
        <div className={`rounded-card border border-slate-200 bg-white p-4 sm:p-5 shadow-sm ${className}`}>
            <div className="flex items-center justify-between">
                <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                    {title}
                </span>
                {badge && (
                    <span className="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                        {badge}
                    </span>
                )}
                {icon && !badge && (
                    <div className="text-slate-400">
                        {icon}
                    </div>
                )}
            </div>

            <div className="mt-2.5 flex items-baseline gap-2">
                <span className="text-2xl sm:text-3xl font-extrabold text-slate-900 tabular-nums">
                    {value}
                </span>
            </div>

            {subtext && (
                <p className="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2">
                    {subtext}
                </p>
            )}
        </div>
    );
}

