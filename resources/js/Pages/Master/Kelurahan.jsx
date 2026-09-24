import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head } from '@inertiajs/react';

export default function MasterKelurahan() {
    return (
        <AuthenticatedLayout header={lang.nav.kelurahan}>
            <Head title={lang.nav.kelurahan} />

            <div className="rounded-card border border-slate-200 bg-white p-6 shadow-sm text-center py-12">
                <h2 className="text-lg font-bold text-slate-900">Kelola Data Kelurahan</h2>
                <p className="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    Halaman admin khusus pengelolaan 6 kelurahan wilayah kerja Puskesmas Payolansek.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
