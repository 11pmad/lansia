import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const now = new Date();
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const currentMonthName = months[now.getMonth()];
    const currentYear = now.getFullYear();

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
                                Laporan Berjalan
                            </span>
                            <h2 className="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">
                                {currentMonthName} {currentYear}
                            </h2>
                        </div>
                        <span className="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 border border-amber-300">
                            Draft (Dalam Pengisian)
                        </span>
                    </div>

                    <div className="mt-4 pt-3 border-t border-teal-100 flex items-center justify-between text-xs sm:text-sm text-slate-600">
                        <span>Cakupan Wilayah: <strong>6 Kelurahan</strong></span>
                        <Link
                            href={route('reports.index')}
                            className="text-teal-700 font-semibold hover:underline inline-flex items-center gap-1"
                        >
                            Buka Laporan
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
                            href={route('reports.index')}
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

                {/* Kartu Ringkasan SPM & Fitur Cepat */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                {lang.dashboard.spmCoverage}
                            </span>
                            <span className="text-xs font-semibold text-teal-700">Tahun {currentYear}</span>
                        </div>
                        <div className="mt-2 flex items-baseline gap-2">
                            <span className="text-3xl font-extrabold text-slate-900 tabular-nums">
                                — %
                            </span>
                            <span className="text-xs text-slate-500">Target Tahunan: 100%</span>
                        </div>
                        <p className="mt-2 text-xs text-slate-500 border-t border-slate-100 pt-2">
                            Persentase lansia usia &gt;60 tahun yang dilayani sesuai standar SPM.
                        </p>
                    </div>

                    <div className="rounded-card border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                Wilayah Kerja (6 Kelurahan)
                            </span>
                            <span className="text-xs font-semibold text-teal-700">Payakumbuh</span>
                        </div>
                        <div className="mt-3 flex flex-wrap gap-1.5">
                            {['PAYOLANSEK', 'BULBA', 'PAKAN SINAYAN', 'KUBU GADANG', 'KOTO TANGAH', 'TALANG'].map((kel) => (
                                <span
                                    key={kel}
                                    className="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 text-xs font-medium"
                                >
                                    {kel}
                                </span>
                            ))}
                        </div>
                        <p className="mt-3 text-xs text-slate-500 border-t border-slate-100 pt-2">
                            Format Baku: Puskesmas Payolansek TA 2026.
                        </p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
