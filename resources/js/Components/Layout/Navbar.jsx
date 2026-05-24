import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X, Bell, LayoutDashboard, FileText } from 'lucide-react';

export default function Navbar() {
    const [open, setOpen] = useState(false);
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <nav className="sticky top-0 z-40 backdrop-blur-md bg-white/80 border-b border-surface-100">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex items-center justify-between h-16">
                    {/* Logo */}
                    <Link href="/" className="flex items-center gap-2">
                        <div className="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center">
                            <FileText size={16} className="text-white" />
                        </div>
                        <span className="font-bold text-surface-900 text-lg">TenderIQ</span>
                    </Link>

                    {/* Desktop nav */}
                    <div className="hidden md:flex items-center gap-6">
                        <Link href="/tenders" className="text-sm font-medium text-surface-700 hover:text-primary-600 transition-colors">
                            Browse Tenders
                        </Link>
                        <Link href="/pricing" className="text-sm font-medium text-surface-700 hover:text-primary-600 transition-colors">
                            Pricing
                        </Link>
                        {user && (
                            <>
                                <Link href="/alerts" className="text-sm font-medium text-surface-700 hover:text-primary-600 transition-colors">
                                    Alerts
                                </Link>
                                <Link href="/dashboard" className="text-sm font-medium text-surface-700 hover:text-primary-600 transition-colors">
                                    Dashboard
                                </Link>
                            </>
                        )}
                    </div>

                    {/* Auth buttons */}
                    <div className="hidden md:flex items-center gap-3">
                        {user ? (
                            <div className="flex items-center gap-3">
                                <span className="text-sm text-surface-600">{user.name}</span>
                                <Link href="/logout" method="post" as="button" className="btn-secondary text-sm">
                                    Sign out
                                </Link>
                            </div>
                        ) : (
                            <>
                                <Link href="/login" className="btn-ghost">Sign in</Link>
                                <Link href="/register" className="btn-primary">Get started</Link>
                            </>
                        )}
                    </div>

                    {/* Mobile toggle */}
                    <button
                        onClick={() => setOpen(!open)}
                        className="md:hidden p-2 rounded-button text-surface-700"
                    >
                        {open ? <X size={20} /> : <Menu size={20} />}
                    </button>
                </div>
            </div>

            {/* Mobile menu */}
            {open && (
                <div className="md:hidden border-t border-surface-100 bg-white px-4 pb-4 pt-2 flex flex-col gap-3 animate-fade-in">
                    <Link href="/tenders" className="py-2 text-sm font-medium text-surface-700">Browse Tenders</Link>
                    <Link href="/pricing" className="py-2 text-sm font-medium text-surface-700">Pricing</Link>
                    {user && (
                        <>
                            <Link href="/alerts" className="py-2 text-sm font-medium text-surface-700">Alerts</Link>
                            <Link href="/dashboard" className="py-2 text-sm font-medium text-surface-700">Dashboard</Link>
                        </>
                    )}
                    <div className="pt-2 border-t border-surface-100 flex flex-col gap-2">
                        {user ? (
                            <Link href="/logout" method="post" as="button" className="btn-secondary w-full text-center">Sign out</Link>
                        ) : (
                            <>
                                <Link href="/login" className="btn-secondary w-full text-center">Sign in</Link>
                                <Link href="/register" className="btn-primary w-full text-center">Get started</Link>
                            </>
                        )}
                    </div>
                </div>
            )}
        </nav>
    );
}
