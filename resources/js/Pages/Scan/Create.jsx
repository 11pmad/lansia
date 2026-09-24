import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head } from '@inertiajs/react';

export default function ScanCreate() {
    return (
        <AuthenticatedLayout header={lang.nav.scan}>
            <Head title={lang.nav.scan} />

            <div className="rounded-card border border-slate-200 bg-white p-6 shadow-sm text-center py-12">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-teal-700 mb-4">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-6 w-6">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                        <circle cx="12" cy="13" r="4" />
                    </svg>
                </div>
                <h2 className="text-lg font-bold text-slate-900">Scan Laporan Tulisan Tangan</h2>
                <p className="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    Modul scan kamera OCR tulisan tangan akan diimplementasikan pada Fase 6.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
