import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head } from '@inertiajs/react';

export default function Statistik() {
    return (
        <AuthenticatedLayout header={lang.nav.stats}>
            <Head title={lang.nav.stats} />

            <div className="rounded-card border border-slate-200 bg-white p-6 shadow-sm text-center py-12">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 mb-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                        <line x1="18" y1="20" x2="18" y2="10" />
                        <line x1="12" y1="20" x2="12" y2="4" />
                        <line x1="6" y1="20" x2="6" y2="14" />
                    </svg>
                </div>
                <h2 className="text-lg font-bold text-slate-900">Statistik & Tren Pelayanan</h2>
                <p className="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    Grafik visualisasi tren kunjungan dan capaian SPM akan diimplementasikan pada Fase 4.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
