import ApplicationLogo from '@/Components/ApplicationLogo';
import BottomNav from '@/Components/BottomNav';
import { lang } from '@/lang';
import { Link, usePage } from '@inertiajs/react';

export default function AuthenticatedLayout({ header, backUrl, children }) {
    const user = usePage().props.auth.user;
    const isAdmin = user?.role === 'admin';

    const navLinks = [
        {
            name: lang.nav.home,
            route: 'dashboard',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            ),
        },
        {
            name: lang.nav.reports,
            route: 'reports.index',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
            ),
        },
        {
            name: lang.nav.scan,
            route: 'scan.create',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                    <circle cx="12" cy="13" r="4" />
                </svg>
            ),
        },
        {
            name: lang.nav.stats,
            route: 'stats.index',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
            ),
        },
        {
            name: 'Ekspor Excel',
            route: 'export.index',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
            ),
        },
    ];

    const adminLinks = [
        {
            name: lang.nav.kelurahan,
            route: 'kelurahan.index',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="M3 21h18" />
                    <path d="M5 21V7l8-4v18" />
                    <path d="M19 21V11l-6-4" />
                    <path d="M9 9v.01" />
                    <path d="M9 12v.01" />
                    <path d="M9 15v.01" />
                    <path d="M9 18v.01" />
                </svg>
            ),
        },
        {
            name: lang.nav.users,
            route: 'users.index',
            icon: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-5 w-5">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            ),
        },
    ];

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col lg:flex-row">
            {/* Desktop Sidebar (>= 1024px) */}
            <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 border-r border-slate-200 bg-white z-30">
                <div className="flex h-16 items-center gap-3 px-6 border-b border-slate-200">
                    <ApplicationLogo className="h-9 w-9 bg-teal-700 text-white rounded-lg" />
                    <div>
                        <div className="font-bold text-base tracking-tight text-slate-900 leading-tight">
                            {lang.app.name}
                        </div>
                        <div className="text-[11px] text-teal-800 font-medium leading-tight">
                            Puskesmas Payolansek
                        </div>
                    </div>
                </div>

                <div className="flex-1 flex flex-col justify-between overflow-y-auto px-4 py-4">
                    <div className="space-y-6">
                        <div>
                            <p className="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                Menu Utama
                            </p>
                            <nav className="space-y-1">
                                {navLinks.map((item) => {
                                    const isActive = route().current(item.route + '*');
                                    return (
                                        <Link
                                            key={item.name}
                                            href={route(item.route)}
                                            className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${
                                                isActive
                                                    ? 'bg-teal-50 text-teal-800 font-semibold'
                                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                            }`}
                                        >
                                            <span className={isActive ? 'text-teal-700' : 'text-slate-400'}>
                                                {item.icon}
                                            </span>
                                            {item.name}
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>

                        {isAdmin && (
                            <div>
                                <p className="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                    Master Data (Admin)
                                </p>
                                <nav className="space-y-1">
                                    {adminLinks.map((item) => {
                                        const isActive = route().current(item.route + '*');
                                        return (
                                            <Link
                                                key={item.name}
                                                href={route(item.route)}
                                                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition ${
                                                    isActive
                                                        ? 'bg-teal-50 text-teal-800 font-semibold'
                                                        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                                }`}
                                            >
                                                <span className={isActive ? 'text-teal-700' : 'text-slate-400'}>
                                                    {item.icon}
                                                </span>
                                                {item.name}
                                            </Link>
                                        );
                                    })}
                                </nav>
                            </div>
                        )}
                    </div>

                    <div className="border-t border-slate-200 pt-4">
                        <div className="flex items-center gap-3 px-2 py-2">
                            <div className="h-9 w-9 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-sm">
                                {user?.name?.charAt(0)?.toUpperCase() || 'U'}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-semibold text-slate-900 truncate">
                                    {user?.name}
                                </p>
                                <p className="text-xs text-slate-500 capitalize">
                                    {user?.role === 'admin' ? lang.auth.roleAdmin : lang.auth.rolePetugas}
                                </p>
                            </div>
                        </div>

                        <div className="mt-2 space-y-1">
                            <Link
                                href={route('profile.edit')}
                                className="flex items-center gap-2 px-3 py-2 text-xs font-medium text-slate-600 rounded-md hover:bg-slate-100"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4 text-slate-400">
                                    <circle cx="12" cy="12" r="3" />
                                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
                                </svg>
                                {lang.nav.profile}
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium text-rose-600 rounded-md hover:bg-rose-50"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" className="h-4 w-4 text-rose-500">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                {lang.nav.logout}
                            </Link>
                        </div>
                    </div>
                </div>
            </aside>

            {/* Mobile Top Header (56px) */}
            <header className="lg:hidden sticky top-0 z-30 h-14 border-b border-slate-200 bg-white/95 backdrop-blur-sm px-4 flex items-center justify-between">
                <div className="flex items-center gap-2 min-w-0">
                    {backUrl ? (
                        <Link
                            href={backUrl}
                            className="flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 active:bg-slate-200"
                            aria-label={lang.common.back}
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" className="h-5 w-5">
                                <path d="m15 18-6-6 6-6" />
                            </svg>
                        </Link>
                    ) : (
                        <ApplicationLogo className="h-8 w-8 bg-teal-700 text-white rounded-lg" />
                    )}
                    <div className="truncate">
                        <span className="font-bold text-base text-slate-900 tracking-tight">
                            {header || lang.app.name}
                        </span>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <span className="inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 border border-teal-200">
                        {user?.role === 'admin' ? 'Admin' : 'Petugas'}
                    </span>
                </div>
            </header>

            {/* Main Content Area */}
            <div className="flex-1 lg:pl-64 flex flex-col min-h-screen">
                <main className="flex-1 w-full max-w-[1100px] mx-auto px-4 py-4 sm:px-6 sm:py-6 pb-24 lg:pb-8">
                    {children}
                </main>
            </div>

            {/* Mobile Bottom Navigation (64px) */}
            <BottomNav />
        </div>
    );
}
