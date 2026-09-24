import { lang } from '@/lang';
import { Link } from '@inertiajs/react';

export default function BottomNav({ activeRoute }) {
    const navItems = [
        {
            name: lang.nav.home,
            route: 'dashboard',
            icon: (active) => (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={active ? 2.5 : 2}
                    className="h-6 w-6"
                >
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            ),
        },
        {
            name: lang.nav.reports,
            route: 'reports.index',
            icon: (active) => (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={active ? 2.5 : 2}
                    className="h-6 w-6"
                >
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                    <polyline points="10 9 9 9 8 9" />
                </svg>
            ),
        },
        {
            name: lang.nav.scan,
            route: 'scan.create',
            isSpecial: true,
            icon: () => (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2.5"
                    className="h-7 w-7 text-white"
                >
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                    <circle cx="12" cy="13" r="4" />
                </svg>
            ),
        },
        {
            name: lang.nav.stats,
            route: 'stats.index',
            icon: (active) => (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={active ? 2.5 : 2}
                    className="h-6 w-6"
                >
                    <line x1="18" y1="20" x2="18" y2="10" />
                    <line x1="12" y1="20" x2="12" y2="4" />
                    <line x1="6" y1="20" x2="6" y2="14" />
                </svg>
            ),
        },
        {
            name: lang.nav.menu,
            route: 'profile.edit',
            icon: (active) => (
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={active ? 2.5 : 2}
                    className="h-6 w-6"
                >
                    <line x1="4" y1="12" x2="20" y2="12" />
                    <line x1="4" y1="6" x2="20" y2="6" />
                    <line x1="4" y1="18" x2="20" y2="18" />
                </svg>
            ),
        },
    ];

    return (
        <nav
            aria-label="Navigasi Utama"
            className="fixed bottom-0 left-0 right-0 z-40 h-16 border-t border-slate-200 bg-white/95 backdrop-blur-sm lg:hidden"
        >
            <div className="grid h-full grid-cols-5 items-center">
                {navItems.map((item) => {
                    const isActive = route().current(item.route + '*');

                    if (item.isSpecial) {
                        return (
                            <div key={item.name} className="flex justify-center -mt-6">
                                <Link
                                    href={route(item.route)}
                                    className="flex h-14 w-14 items-center justify-center rounded-full bg-teal-700 shadow-lg shadow-teal-700/30 transition-transform active:scale-95 focus:outline-none focus:ring-4 focus:ring-teal-500/30"
                                    aria-label="Scan Laporan"
                                >
                                    {item.icon()}
                                </Link>
                            </div>
                        );
                    }

                    return (
                        <Link
                            key={item.name}
                            href={route(item.route)}
                            className={`flex flex-col items-center justify-center py-1 min-h-[48px] transition-colors ${
                                isActive
                                    ? 'text-teal-700 font-semibold'
                                    : 'text-slate-500 hover:text-slate-900'
                            }`}
                        >
                            {item.icon(isActive)}
                            <span className="mt-1 text-[11px] leading-none">
                                {item.name}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
