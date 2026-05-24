import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/UI/Pagination';
import { StatusBadge } from '@/Components/Tender/TenderBadge';
import { Star } from 'lucide-react';

export default function Tenders({ tenders }) {
    return (
        <AppLayout>
            <Head title="Admin — Tenders" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href="/admin" className="btn-ghost text-sm">← Back</Link>
                    <h1 className="text-xl font-bold text-surface-900">Tenders</h1>
                </div>

                <div className="bg-white border border-surface-200 rounded-card shadow-card overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-surface-50 border-b border-surface-200">
                            <tr>
                                {['#', 'Title', 'Org', 'Status', 'Closing', 'AI', 'Featured'].map(h => (
                                    <th key={h} className="text-left text-xs font-semibold text-surface-400 uppercase tracking-wide px-4 py-3">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {tenders.data?.map(tender => (
                                <TenderRow key={tender.id} tender={tender} />
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={tenders} baseUrl="/admin/tenders" />
            </div>
        </AppLayout>
    );
}

function TenderRow({ tender }) {
    const { patch } = useForm();

    return (
        <tr className="border-b border-surface-100 hover:bg-surface-50 transition-colors">
            <td className="px-4 py-3 text-xs text-surface-400 font-mono">{tender.tender_number}</td>
            <td className="px-4 py-3 max-w-xs">
                <Link
                    href={`/tenders/${tender.tender_number}`}
                    className="text-surface-800 hover:text-primary-600 font-medium line-clamp-1"
                >
                    {tender.title}
                </Link>
            </td>
            <td className="px-4 py-3 text-surface-400 max-w-xs">
                <span className="line-clamp-1">{tender.organization_name}</span>
            </td>
            <td className="px-4 py-3"><StatusBadge status={tender.status} /></td>
            <td className="px-4 py-3 text-surface-400 text-xs whitespace-nowrap">
                {tender.closing_at ? new Date(tender.closing_at).toLocaleDateString() : '—'}
            </td>
            <td className="px-4 py-3">
                {tender.is_summarized ? (
                    <span className="text-emerald-600 text-xs font-medium">Done</span>
                ) : (
                    <span className="text-surface-300 text-xs">Pending</span>
                )}
            </td>
            <td className="px-4 py-3">
                <button
                    onClick={() => patch(`/admin/tenders/${tender.id}/featured`)}
                    className={`p-1 rounded transition-colors ${
                        tender.is_featured ? 'text-accent-500' : 'text-surface-300 hover:text-accent-400'
                    }`}
                >
                    <Star size={14} fill={tender.is_featured ? 'currentColor' : 'none'} />
                </button>
            </td>
        </tr>
    );
}
