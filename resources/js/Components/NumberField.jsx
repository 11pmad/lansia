import React from 'react';

/**
 * NumberField sesuai ketentuan docs/07_SKILL_DESIGN.md:
 * - inputmode="numeric" pattern="[0-9]*" (bukan type="number")
 * - font 18-20px tabular-nums rata kanan
 * - select-all saat fokus
 * - tombol - / + opsional untuk kenyamanan input mobile
 * - tinggi sentuh minimal 48px
 */
export default function NumberField({
    id,
    label,
    value,
    onChange,
    disabled = false,
    placeholder = '0',
    helpText,
    showStepper = false,
    className = '',
}) {
    const handleInputChange = (e) => {
        const raw = e.target.value;
        // Hanya izinkan angka
        if (raw === '') {
            onChange(null);
            return;
        }
        const cleaned = raw.replace(/[^0-9]/g, '');
        onChange(cleaned === '' ? null : parseInt(cleaned, 10));
    };

    const handleStep = (delta) => {
        if (disabled) return;
        const current = value === null || value === undefined ? 0 : Number(value);
        const next = Math.max(0, current + delta);
        onChange(next);
    };

    const displayValue = value === null || value === undefined ? '' : String(value);

    return (
        <div className={`space-y-1 ${className}`}>
            {label && (
                <label
                    htmlFor={id}
                    className="block text-sm font-semibold text-slate-800 leading-tight"
                >
                    {label}
                </label>
            )}

            <div className="relative flex items-center rounded-lg border border-slate-300 bg-white shadow-sm focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/30">
                {showStepper && (
                    <button
                        type="button"
                        tabIndex={-1}
                        disabled={disabled || (value === 0 || value === null)}
                        onClick={() => handleStep(-1)}
                        className="flex h-12 w-12 items-center justify-center rounded-l-lg border-r border-slate-200 text-slate-600 hover:bg-slate-100 active:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed select-none"
                        aria-label="Kurang satu"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5">
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                    </button>
                )}

                <input
                    id={id}
                    type="text"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    value={displayValue}
                    placeholder={placeholder}
                    disabled={disabled}
                    onChange={handleInputChange}
                    onFocus={(e) => e.target.select()}
                    className={`w-full min-h-[48px] bg-transparent px-3.5 py-2.5 text-right font-bold text-slate-900 text-lg tabular-nums placeholder:text-slate-300 border-0 focus:ring-0 focus:outline-none disabled:bg-slate-100 disabled:text-slate-400`}
                />

                {showStepper && (
                    <button
                        type="button"
                        tabIndex={-1}
                        disabled={disabled}
                        onClick={() => handleStep(1)}
                        className="flex h-12 w-12 items-center justify-center rounded-r-lg border-l border-slate-200 text-slate-600 hover:bg-slate-100 active:bg-slate-200 disabled:opacity-30 disabled:cursor-not-allowed select-none"
                        aria-label="Tambah satu"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                    </button>
                )}
            </div>

            {helpText && (
                <p className="text-xs text-slate-500 leading-tight">
                    {helpText}
                </p>
            )}
        </div>
    );
}
