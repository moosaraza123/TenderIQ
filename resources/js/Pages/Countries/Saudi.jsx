import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TenderCard from '@/Components/Tender/TenderCard';

export default function Saudi({ tenders = [] }) {
    return (
        <AppLayout>
            <Head>
                <title>Saudi Arabia Government Tenders — TenderIQ</title>
                <meta name="description" content="Browse active Saudi Arabia government tenders from Etimad platform. Updated daily." />
            </Head>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="mb-10">
                    <div className="flex items-center gap-3 mb-3">
                        <span className="text-4xl">🇸🇦</span>
                        <h1 className="text-3xl font-extrabold text-surface-900">Saudi Arabia Government Tenders</h1>
                    </div>
                    <p className="text-surface-500 max-w-2xl">
                        Active procurement notices from Etimad — the official Saudi government e-procurement platform.
                        Requires Pro plan ($99/mo).
                    </p>
                    <div className="flex gap-4 mt-4 text-sm text-surface-400">
                        <span>🏛️ Etimad Platform</span>
                    </div>
                </div>

                {tenders.length > 0 ? (
                    <div className="flex flex-col gap-3">
                        {tenders.map((tender, i) => (
                            <TenderCard key={tender.id} tender={tender} index={i} />
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-20">
                        <div className="text-5xl mb-4">🔒</div>
                        <h2 className="text-xl font-semibold text-surface-900 mb-2">Saudi Tenders Require Pro Plan</h2>
                        <p className="text-surface-500 mb-6">Unlock Saudi Arabia tenders for $99/month.</p>
                        <Link href="/pricing" className="btn-primary inline-block">View Pricing</Link>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
