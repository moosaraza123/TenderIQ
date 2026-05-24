import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import AlertCard from '@/Components/Alert/AlertCard';
import AlertForm from '@/Components/Alert/AlertForm';
import EmptyState from '@/Components/UI/EmptyState';
import { Bell, Plus, Lock } from 'lucide-react';

export default function Index({ alerts = [], canCreate = true, limit = 1 }) {
    const [showForm, setShowForm] = useState(false);

    return (
        <AppLayout>
            <Head title="My Alerts" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-surface-900">My Alerts</h1>
                        <p className="text-sm text-surface-400 mt-1">
                            {alerts.length} of {limit >= 9999 ? '∞' : limit} alerts used
                        </p>
                    </div>
                    {canCreate ? (
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="btn-primary flex items-center gap-2"
                        >
                            <Plus size={14} />
                            New alert
                        </button>
                    ) : (
                        <div className="flex items-center gap-2 text-sm text-surface-400">
                            <Lock size={14} />
                            <span>Limit reached</span>
                        </div>
                    )}
                </div>

                {/* Create form */}
                {showForm && (
                    <div className="bg-white border border-surface-200 rounded-card shadow-card p-6 mb-6 animate-slide-up">
                        <h2 className="text-sm font-semibold text-surface-800 mb-4">Create new alert</h2>
                        <AlertForm onSuccess={() => setShowForm(false)} />
                    </div>
                )}

                {/* Alert list */}
                {alerts.length === 0 ? (
                    <EmptyState
                        icon={Bell}
                        heading="No alerts yet"
                        sub="Create an alert to get notified when matching tenders are posted"
                        action={() => setShowForm(true)}
                        actionLabel="Create first alert"
                    />
                ) : (
                    <div className="flex flex-col gap-3">
                        {alerts.map(alert => (
                            <AlertCard key={alert.id} alert={alert} />
                        ))}
                    </div>
                )}

                {!canCreate && (
                    <div className="mt-6 bg-amber-50 border border-amber-200 rounded-card p-4 text-sm text-amber-800">
                        You've reached your alert limit ({alerts.length}/{limit}).{' '}
                        <a href="/pricing" className="font-semibold underline">Upgrade your plan</a> to create more alerts.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
