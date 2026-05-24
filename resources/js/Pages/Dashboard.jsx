import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { LayoutDashboard, Bell, Clock, Eye, ArrowRight } from 'lucide-react';

export default function Dashboard() {
    const { auth } = usePage().props;
    const user     = auth?.user;
    const hour     = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Greeting */}
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-surface-900">
                        {greeting}, {user?.name?.split(' ')[0]} 👋
                    </h1>
                    <p className="text-sm text-surface-400 mt-1">
                        {new Date().toLocaleDateString('en-PK', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}
                    </p>
                </div>

                {/* Upgrade CTA for free users */}
                {user?.subscription_plan === 'free' && (
                    <div className="bg-gradient-to-r from-primary-500 to-primary-600 rounded-card p-5 text-white mb-8 flex items-center justify-between">
                        <div>
                            <p className="font-semibold mb-1">Unlock full access</p>
                            <p className="text-primary-100 text-sm">
                                Upgrade to Basic for AI summaries, unlimited views, and PDF downloads.
                            </p>
                        </div>
                        <Link href="/register" className="bg-white text-primary-600 hover:bg-primary-50 font-medium text-sm px-4 py-2 rounded-button shrink-0 ml-4">
                            Upgrade →
                        </Link>
                    </div>
                )}

                {/* Quick links */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <QuickCard
                        href="/tenders"
                        icon={LayoutDashboard}
                        title="Browse Tenders"
                        desc="Find the latest government tenders"
                    />
                    <QuickCard
                        href="/alerts"
                        icon={Bell}
                        title="My Alerts"
                        desc="Manage your keyword alerts"
                    />
                    <QuickCard
                        href="/tenders?sort=closing_soon"
                        icon={Clock}
                        title="Closing Soon"
                        desc="Tenders closing in the next 7 days"
                    />
                </div>

                {/* Plan info */}
                <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-sm font-semibold text-surface-800">Your Plan</h2>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className={`px-3 py-1 rounded-badge text-sm font-semibold capitalize ${
                            user?.subscription_plan === 'pro'   ? 'bg-purple-100 text-purple-700' :
                            user?.subscription_plan === 'basic' ? 'bg-primary-100 text-primary-700' :
                            'bg-surface-100 text-surface-600'
                        }`}>
                            {user?.subscription_plan ?? 'free'}
                        </span>
                        {user?.subscription_expires_at && (
                            <span className="text-xs text-surface-400">
                                Expires {new Date(user.subscription_expires_at).toLocaleDateString()}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function QuickCard({ href, icon: Icon, title, desc }) {
    return (
        <Link
            href={href}
            className="bg-white border border-surface-200 rounded-card shadow-card p-5 hover:shadow-card-hover hover:border-primary-200 hover:-translate-y-px transition-all duration-200 flex items-start gap-4"
        >
            <div className="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center shrink-0">
                <Icon size={18} className="text-primary-500" />
            </div>
            <div>
                <p className="font-semibold text-surface-800 text-sm mb-1">{title}</p>
                <p className="text-xs text-surface-400">{desc}</p>
            </div>
        </Link>
    );
}
