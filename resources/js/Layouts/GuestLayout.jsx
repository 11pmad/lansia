import ApplicationLogo from '@/Components/ApplicationLogo';
import { lang } from '@/lang';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-8 sm:px-6">
            <div className="flex flex-col items-center text-center">
                <Link href="/" className="flex flex-col items-center gap-2 group">
                    <ApplicationLogo className="h-16 w-16 bg-teal-700 text-white rounded-2xl shadow-md transition-transform group-hover:scale-105" />
                    <span className="text-2xl font-bold tracking-tight text-slate-900">
                        {lang.app.name}
                    </span>
                </Link>
                <p className="mt-1 text-sm font-medium text-teal-800">
                    {lang.app.fullName}
                </p>
                <p className="text-xs text-slate-500">
                    {lang.app.city}
                </p>
            </div>

            <div className="mt-6 w-full max-w-[420px] rounded-card border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                {children}
            </div>

            <footer className="mt-8 text-center text-xs text-slate-400">
                &copy; 2026 Puskesmas Payolansek · Format Pelaporan Dinkes
            </footer>
        </div>
    );
}
