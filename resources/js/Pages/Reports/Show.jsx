import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatNumber, formatPercentage, getMonthName } from '@/lib/format';
import { lang } from '@/lang';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function ReportsShow({
    report,
    kunjunganSummary,
    layananSummary,
    warnings = [],
    canFinalize,
    canReopen,
}) {
    const [confirmingFinalize, setConfirmingFinalize] = useState(false);
    const [confirmingReopen, setConfirmingReopen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const monthName = getMonthName(report.month);
    const isDraft = report.status === 'draft';
    const isFinal = report.status === 'final';

    const handleFinalize = () => {
        setProcessing(true);
        router.post(
            route('reports.finalize', report.id),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setConfirmingFinalize(false);
                },
            }
        );
    };

    const handleReopen = () => {
        setProcessing(true);
        router.post(
            route('reports.reopen', report.id),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setConfirmingReopen(false);
                },
            }
        );
    };

    return (
        <AuthenticatedLayout
            header={`Laporan ${monthName} ${report.year}`}
            backUrl={route('reports.index', { year: report.year })}
        >
            <Head title={`Laporan ${monthName} ${report.year}`} />

            <div className="space-y-6">
                {/* Header Status & Informasi */}
                <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl sm:text-2xl font-bold text-slate-900">
                                    Laporan Bulan {monthName} {report.year}
                                </h1>
                                {isFinal ? (
                                    <span className="inline-flex items-center gap-1 rounded-md bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-300">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-3.5 w-3.5">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg>
                                        Final (Terkunci)
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-300">
                                        Draft (Dapat Diedit)
                                    </span>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                Puskesmas Payolansek · Terakhir diperbarui: {report.updated_at || '—'}
                            </p>
                        </div>

                        {/* Tombol Kunci / Buka Kunci */}
                        <div className="flex items-center gap-2">
                            {isDraft && canFinalize && (
                                <button
                                    type="button"
                                    onClick={() => setConfirmingFinalize(true)}
                                    className="flex min-h-[44px] items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 active:bg-emerald-900 transition"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                    Finalisasi Laporan
                                </button>
                            )}

                            {isFinal && canReopen && (
                                <button
                                    type="button"
                                    onClick={() => setConfirmingReopen(true)}
                                    className="flex min-h-[44px] items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-bold text-amber-900 hover:bg-amber-100 active:bg-amber-200 transition"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-4 w-4">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                                    </svg>
                                    Buka Kunci (Admin)
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Dua Tombol Pengisian Form */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link
                        href={route('forms.kunjungan.edit', report.id)}
                        className="group flex flex-col justify-between rounded-card border-2 border-teal-600 bg-white p-5 shadow-sm transition hover:bg-teal-50/50"
                    >
                        <div>
                            <div className="flex items-center justify-between">
                                <span className="rounded-lg bg-teal-100 p-2.5 text-teal-800">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <polyline points="16 11 18 13 22 9" />
                                    </svg>
                                </span>
                                <span className="text-xs font-bold text-teal-700 group-hover:underline">
                                    {isDraft ? 'Isi / Edit Data →' : 'Lihat Data →'}
                                </span>
                            </div>
                            <h2 className="mt-4 text-lg font-bold text-slate-900">
                                Form Kunjungan Lansia
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                Data umum, sasaran kelompok umur, kunjungan dalam & luar gedung, dan tingkat kemandirian.
                            </p>
                        </div>

                        <div className="mt-4 border-t border-slate-100 pt-3 flex justify-between text-xs font-semibold text-slate-700">
                            <span>Total Kunjungan:</span>
                            <span className="font-bold text-teal-800 tabular-nums">
                                {formatNumber(kunjunganSummary?.total_kunjungan_bulan)}
                            </span>
                        </div>
                    </Link>

                    <Link
                        href={route('forms.layanan.edit', report.id)}
                        className="group flex flex-col justify-between rounded-card border-2 border-teal-600 bg-white p-5 shadow-sm transition hover:bg-teal-50/50"
                    >
                        <div>
                            <div className="flex items-center justify-between">
                                <span className="rounded-lg bg-teal-100 p-2.5 text-teal-800">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                                        <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                    </svg>
                                </span>
                                <span className="text-xs font-bold text-teal-700 group-hover:underline">
                                    {isDraft ? 'Isi / Edit Data →' : 'Lihat Data →'}
                                </span>
                            </div>
                            <h2 className="mt-4 text-lg font-bold text-slate-900">
                                Form Layanan Lansia
                            </h2>
                            <p className="mt-1 text-xs text-slate-500">
                                Sasaran, 14 jenis kelainan/penyakit (L/P), tindakan pengobatan, konseling, dan penyuluhan.
                            </p>
                        </div>

                        <div className="mt-4 border-t border-slate-100 pt-3 flex justify-between text-xs font-semibold text-slate-700">
                            <span>Total Temuan Kelainan:</span>
                            <span className="font-bold text-teal-800 tabular-nums">
                                {formatNumber(layananSummary?.kelainan_total)}
                            </span>
                        </div>
                    </Link>
                </div>

                {/* Ringkasan Perhitungan Akumulasi Bulan Ini */}
                <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="font-bold text-base text-slate-900">
                                Capaian & Ringkasan Bulan {monthName}
                            </h3>
                            <p className="text-xs text-slate-500">
                                Akumulasi 6 kelurahan (Dihitung otomatis oleh sistem)
                            </p>
                        </div>

                        <Link
                            href={route('reports.recap', report.id)}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-800 hover:bg-slate-100"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4 text-slate-600">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M3 9h18" />
                                <path d="M9 21V9" />
                            </svg>
                            Tabel Rekap Lengkap
                        </Link>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                        <div className="rounded-lg bg-teal-50/70 p-3.5 border border-teal-100">
                            <span className="text-[11px] font-bold text-teal-800 uppercase">Capaian SPM &gt;60 Th</span>
                            <div className="mt-1 text-2xl font-extrabold text-teal-900 tabular-nums">
                                {formatPercentage(kunjunganSummary?.spm_pct)}
                            </div>
                            <span className="text-[11px] text-teal-700">
                                {kunjunganSummary?.spm_total || 0} / {kunjunganSummary?.total_lansia_60 || 0} lansia
                            </span>
                        </div>

                        <div className="rounded-lg bg-slate-50 p-3.5 border border-slate-200">
                            <span className="text-[11px] font-bold text-slate-500 uppercase">Kunjungan Dalam</span>
                            <div className="mt-1 text-2xl font-extrabold text-slate-900 tabular-nums">
                                {formatNumber(kunjunganSummary?.total_in)}
                            </div>
                            <span className="text-[11px] text-slate-500">Puskesmas</span>
                        </div>

                        <div className="rounded-lg bg-slate-50 p-3.5 border border-slate-200">
                            <span className="text-[11px] font-bold text-slate-500 uppercase">Kunjungan Luar</span>
                            <div className="mt-1 text-2xl font-extrabold text-slate-900 tabular-nums">
                                {formatNumber(kunjunganSummary?.total_out)}
                            </div>
                            <span className="text-[11px] text-slate-500">Posyandu Lansia</span>
                        </div>

                        <div className="rounded-lg bg-slate-50 p-3.5 border border-slate-200">
                            <span className="text-[11px] font-bold text-slate-500 uppercase">Total Temuan</span>
                            <div className="mt-1 text-2xl font-extrabold text-slate-900 tabular-nums">
                                {formatNumber(layananSummary?.kelainan_total)}
                            </div>
                            <span className="text-[11px] text-slate-500">14 Jenis Kelainan</span>
                        </div>
                    </div>
                </div>

                {/* Peringatan Validasi Sebelum Finalisasi (Bila Ada) */}
                {warnings.length > 0 && isDraft && (
                    <div className="rounded-card border border-amber-200 bg-amber-50 p-4">
                        <div className="flex items-start gap-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5 text-amber-700 shrink-0 mt-0.5">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                <line x1="12" y1="9" x2="12" y2="13" />
                                <line x1="12" y1="17" x2="12.01" y2="17" />
                            </svg>
                            <div>
                                <h4 className="font-bold text-sm text-amber-900">
                                    Catatan Verifikasi Data ({warnings.length} Catatan)
                                </h4>
                                <ul className="mt-2 list-disc list-inside space-y-1 text-xs text-amber-800">
                                    {warnings.map((warn, i) => (
                                        <li key={i}>{warn}</li>
                                    ))}
                                </ul>
                                <p className="mt-2 text-xs text-amber-700">
                                    Catatan di atas merupakan peringatan kewajaran data (bukan error). Anda tetap dapat memfinalisasi jika data memang sudah benar.
                                </p>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Konfirmasi Finalisasi */}
            {confirmingFinalize && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
                        <div className="flex items-center gap-3 text-emerald-800">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-6 w-6">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900">Finalisasi Laporan?</h3>
                                <p className="text-xs text-slate-500">Laporan akan dikunci dari pengeditan.</p>
                            </div>
                        </div>

                        <p className="text-sm text-slate-600">
                            Setelah difinalisasi, formulir tidak dapat diubah kembali kecuali dibuka oleh Administrator. Pastikan seluruh kelurahan telah terisi dengan benar.
                        </p>

                        {warnings.length > 0 && (
                            <div className="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 border border-amber-200">
                                <strong>Perhatian:</strong> Terdapat {warnings.length} catatan verifikasi angka janggal.
                            </div>
                        )}

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => setConfirmingFinalize(false)}
                                className="min-h-[44px] rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                disabled={processing}
                                onClick={handleFinalize}
                                className="min-h-[44px] rounded-lg bg-emerald-700 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-800 active:bg-emerald-900 disabled:opacity-50"
                            >
                                {processing ? 'Memproses...' : 'Ya, Finalisasi'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Konfirmasi Buka Kunci (Admin) */}
            {confirmingReopen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
                        <div className="flex items-center gap-3 text-amber-800">
                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-6 w-6">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                                </svg>
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-slate-900">Buka Kunci Laporan?</h3>
                                <p className="text-xs text-slate-500">Khusus Administrator</p>
                            </div>
                        </div>

                        <p className="text-sm text-slate-600">
                            Laporan akan diubah kembali ke status <strong>Draft</strong> sehingga petugas dapat mengedit kembali isian form.
                        </p>

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => setConfirmingReopen(false)}
                                className="min-h-[44px] rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                disabled={processing}
                                onClick={handleReopen}
                                className="min-h-[44px] rounded-lg bg-amber-700 px-5 py-2 text-sm font-bold text-white hover:bg-amber-800 active:bg-amber-900 disabled:opacity-50"
                            >
                                {processing ? 'Memproses...' : 'Buka Kunci'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
