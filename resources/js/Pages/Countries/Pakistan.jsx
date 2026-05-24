import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TenderCard from '@/Components/Tender/TenderCard';
import Pagination from '@/Components/UI/Pagination';

export default function Pakistan({ tenders = [] }) {
    return (
        <AppLayout>
            <Head>
                <title>Pakistan Government Tenders — PPRA, SPPRA, KPPRA | TenderIQ</title>
                <meta name="description" content="Browse all Pakistan government tenders from PPRA Federal, SPPRA Sindh, KPPRA KPK, and BPPRA Balochistan. Free access." />
            </Head>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div className="mb-10">
                    <div className="flex items-center gap-3 mb-3">
                        <span className="text-4xl">🇵🇰</span>
                        <h1 className="text-3xl font-extrabold text-surface-900">Pakistan Government Tenders</h1>
                    </div>
                    <p className="text-surface-500 max-w-2xl">
                        Free access to all Pakistan government procurement notices from PPRA Federal,
                        SPPRA Sindh, KPPRA KPK, and BPPRA Balochistan. Updated every 6 hours.
                    </p>
                    <div className="flex gap-4 mt-4 text-sm text-surface-400">
                        <span>🏛️ PPRA Federal</span>
                        <span>🌊 SPPRA (Sindh)</span>
                        <span>🏔️ KPPRA (KPK)</span>
                        <span>🏜️ BPPRA (Balochistan)</span>
                    </div>
                    <span className="inline-block mt-3 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">
                        Free — No account required
                    </span>
                </div>

                {tenders.length > 0 ? (
                    <div className="flex flex-col gap-3">
                        {tenders.map((tender, i) => (
                            <TenderCard key={tender.id} tender={tender} index={i} />
                        ))}
                    </div>
                ) : (
                    <p className="text-center text-surface-400 py-20">No tenders found.</p>
                )}
            </div>
        </AppLayout>
    );
}
