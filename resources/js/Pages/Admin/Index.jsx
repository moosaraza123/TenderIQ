import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Activity, Users, FileText, RefreshCw, ExternalLink } from 'lucide-react';

export default function Index({ scraperStats = {}, userStats = {} }) {
    const { post, processing } = useForm();

    function triggerScraper(e) {
        e.preventDefault();
        post('/admin/trigger-scraper');
    }

    return (
        <AppLayout>
            <Head title="Admin Panel" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-bold text-surface-900">Admin Panel</h1>
                    <a
                        href="/horizon"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn-secondary flex items-center gap-2 text-sm"
                    >
                        <ExternalLink size={14} />
                        Open Horizon
                    </a>
                </div>

                {/* Scraper stats */}
                <div className="bg-white border border-surface-200 rounded-card shadow-card p-5 mb-6">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-sm font-semibold text-surface-800 flex items-center gap-2">
                            <Activity size={16} className="text-primary-500" />
                            Scraper Status
                        </h2>
                        <form onSubmit={triggerScraper}>
                            <button type="submit" disabled={processing} className="btn-secondary flex items-center gap-2 text-sm">
                                <RefreshCw size={13} className={processing ? 'animate-spin' : ''} />
                                {processing ? 'Dispatching…' : 'Run scraper now'}
                            </button>
                        </form>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <Stat label="Last run" value={scraperStats.last_run ?? 'Never'} />
                        <Stat label="Total tenders" value={(scraperStats.total_tenders ?? 0).toLocaleString()} />
                        <Stat label="New today" value={(scraperStats.today_new ?? 0).toLocaleString()} />
                    </div>
                </div>

                {/* User stats */}
                <div className="bg-white border border-surface-200 rounded-card shadow-card p-5 mb-6">
                    <h2 className="text-sm font-semibold text-surface-800 flex items-center gap-2 mb-4">
                        <Users size={16} className="text-primary-500" />
                        Users
                    </h2>
                    <div className="grid grid-cols-4 gap-4">
                        <Stat label="Total" value={(userStats.total ?? 0).toLocaleString()} />
                        <Stat label="Free" value={(userStats.free ?? 0).toLocaleString()} />
                        <Stat label="Basic" value={(userStats.basic ?? 0).toLocaleString()} />
                        <Stat label="Pro" value={(userStats.pro ?? 0).toLocaleString()} />
                    </div>
                </div>

                {/* Navigation */}
                <div className="flex gap-3">
                    <Link href="/admin/tenders" className="btn-secondary flex items-center gap-2 text-sm">
                        <FileText size={14} />
                        Manage Tenders
                    </Link>
                    <Link href="/admin/users" className="btn-secondary flex items-center gap-2 text-sm">
                        <Users size={14} />
                        Manage Users
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}

function Stat({ label, value }) {
    return (
        <div>
            <p className="text-xs text-surface-400 mb-1">{label}</p>
            <p className="text-lg font-bold text-surface-900">{value}</p>
        </div>
    );
}
