import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head } from '@inertiajs/react';

export default function ReportsIndex() {
    return (
        <AuthenticatedLayout header={lang.nav.reports}>
            <Head title={lang.nav.reports} />

            <div className="rounded-card border border-slate-200 bg-white p-6 shadow-sm text-center py-12">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 mb-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                    </svg>
                </div>
                <h2 className="text-lg font-bold text-slate-900">Daftar Laporan Bulanan</h2>
                <p className="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    Modul laporan bulanan (12 kartu bulan) akan diimplementasikan pada Fase 3.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
