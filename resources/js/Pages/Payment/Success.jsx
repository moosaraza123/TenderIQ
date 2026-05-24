import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const PLAN_LABELS = {
    basic:      { name: 'Basic', emoji: '🇦🇪', desc: 'UAE tenders are now unlocked.' },
    pro:        { name: 'Pro',   emoji: '🇸🇦', desc: 'UAE + Saudi Arabia tenders are now unlocked.' },
    enterprise: { name: 'Enterprise', emoji: '🌍', desc: 'All worldwide tenders are now unlocked.' },
};

export default function Success({ plan = 'basic' }) {
    const info = PLAN_LABELS[plan] || PLAN_LABELS.basic;

    return (
        <AppLayout>
            <Head title="Subscription Active — TenderIQ" />
            <div className="max-w-lg mx-auto px-4 py-24 text-center">
                <div className="text-6xl mb-6">{info.emoji}</div>
                <h1 className="text-3xl font-bold text-surface-900 mb-3">
                    Welcome to {info.name}!
                </h1>
                <p className="text-surface-600 mb-8">{info.desc}</p>
                <Link href="/tenders" className="btn-primary inline-block">
                    Browse Tenders →
                </Link>
            </div>
        </AppLayout>
    );
}
