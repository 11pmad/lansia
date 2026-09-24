import React from 'react';

/**
 * KelurahanTabs sesuai docs/07_SKILL_DESIGN.md:
 * - Baris horizontal chip/tab yang dapat digeser (scrollable)
 * - Kelurahan aktif berwarna primer teal
 * - Titik hijau bila lengkap terisi
 */
export default function KelurahanTabs({
    kelurahans = [],
    activeId,
    onSelect,
    completionMap = {},
}) {
    return (
        <div className="relative -mx-4 px-4 sm:mx-0 sm:px-0">
            <div className="flex gap-2 overflow-x-auto pb-2 scrollbar-none no-scrollbar">
                {kelurahans.map((kel) => {
                    const isActive = kel.id === activeId;
                    const isComplete = Boolean(completionMap[kel.id]);

                    return (
                        <button
                            key={kel.id}
                            type="button"
                            onClick={() => onSelect(kel.id)}
                            className={`flex min-h-[44px] shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition select-none ${
                                isActive
                                    ? 'bg-teal-700 text-white shadow-sm ring-2 ring-teal-700/20'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100'
                            }`}
                        >
                            <span
                                className={`h-2 w-2 rounded-full ${
                                    isComplete
                                        ? isActive ? 'bg-emerald-300' : 'bg-emerald-500'
                                        : isActive ? 'bg-teal-300' : 'bg-slate-300'
                                }`}
                            />
                            <span>{kel.name}</span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
