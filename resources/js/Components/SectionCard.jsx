import React from 'react';

/**
 * SectionCard (Accordion) sesuai docs/07_SKILL_DESIGN.md:
 * - Menampilkan judul bagian + status keterisian (Kosong / Sebagian / Lengkap)
 * - Hanya satu bagian terbuka pada satu waktu
 * - Header accordion memiliki target sentuh minimal 48px
 */
export default function SectionCard({
    title,
    subtitle,
    status = 'empty', // 'empty' | 'partial' | 'complete'
    isOpen,
    onToggle,
    children,
    badge,
    className = '',
}) {
    const statusBadges = {
        empty: {
            text: 'Belum diisi',
            bg: 'bg-slate-100 text-slate-600 border-slate-200',
            dot: 'bg-slate-400',
        },
        partial: {
            text: 'Sebagian',
            bg: 'bg-amber-50 text-amber-800 border-amber-200',
            dot: 'bg-amber-500',
        },
        complete: {
            text: 'Lengkap',
            bg: 'bg-emerald-50 text-emerald-800 border-emerald-200',
            dot: 'bg-emerald-500',
        },
    };

    const currentBadge = statusBadges[status] || statusBadges.empty;

    return (
        <div className={`rounded-card border transition-all ${
            isOpen ? 'border-teal-600 bg-white shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'
        } ${className}`}>
            <button
                type="button"
                onClick={onToggle}
                className="flex w-full min-h-[56px] items-center justify-between px-4 py-3.5 text-left focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-inset rounded-card select-none"
                aria-expanded={isOpen}
            >
                <div className="flex-1 min-w-0 pr-2">
                    <div className="flex items-center gap-2">
                        <span className={`h-2.5 w-2.5 rounded-full ${currentBadge.dot}`} />
                        <h3 className="font-bold text-base text-slate-900 truncate">
                            {title}
                        </h3>
                    </div>
                    {subtitle && (
                        <p className="mt-0.5 text-xs text-slate-500 pl-4.5 truncate">
                            {subtitle}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-2 shrink-0">
                    {badge ? (
                        badge
                    ) : (
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border ${currentBadge.bg}`}>
                            {currentBadge.text}
                        </span>
                    )}

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        className={`h-5 w-5 text-slate-400 transition-transform duration-200 ${
                            isOpen ? 'rotate-180 text-teal-700' : ''
                        }`}
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
            </button>

            {isOpen && (
                <div className="border-t border-slate-100 px-4 py-4 sm:px-5 sm:py-5">
                    {children}
                </div>
            )}
        </div>
    );
}
