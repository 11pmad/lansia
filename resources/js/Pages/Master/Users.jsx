import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { lang } from '@/lang';
import { Head } from '@inertiajs/react';

export default function MasterUsers() {
    return (
        <AuthenticatedLayout header={lang.nav.users}>
            <Head title={lang.nav.users} />

            <div className="rounded-card border border-slate-200 bg-white p-6 shadow-sm text-center py-12">
                <h2 className="text-lg font-bold text-slate-900">Kelola Pengguna (Admin)</h2>
                <p className="mt-1 text-sm text-slate-500 max-w-sm mx-auto">
                    Halaman admin khusus pengelolaan akun petugas dan administrator.
                </p>
            </div>
        </AuthenticatedLayout>
    );
}
