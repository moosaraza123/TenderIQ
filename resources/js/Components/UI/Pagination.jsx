import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export default function Pagination({ meta, baseUrl = '/tenders', filters = {} }) {
    if (!meta || meta.last_page <= 1) return null;

    const { current_page, last_page, total } = meta;

    function goTo(page) {
        const params = { ...filters, page };
        const clean  = Object.fromEntries(Object.entries(params).filter(([, v]) => v));
        router.get(baseUrl, clean, { preserveState: true });
    }

    const pages = [];
    const start = Math.max(1, current_page - 2);
    const end   = Math.min(last_page, current_page + 2);

    for (let i = start; i <= end; i++) pages.push(i);

    return (
        <div className="flex items-center justify-between mt-6">
            <p className="text-sm text-surface-400">
                Showing page <span className="font-medium text-surface-700">{current_page}</span> of{' '}
                <span className="font-medium text-surface-700">{last_page}</span>{total != null ? ` (${total.toLocaleString()} total)` : ''}
            </p>

            <div className="flex items-center gap-1">
                <button
                    onClick={() => goTo(current_page - 1)}
                    disabled={current_page === 1}
                    className="p-1.5 rounded-button text-surface-700 hover:bg-surface-100 disabled:opacity-30 disabled:cursor-not-allowed"
                >
                    <ChevronLeft size={16} />
                </button>

                {start > 1 && (
                    <>
                        <PageBtn page={1} current={current_page} onClick={goTo} />
                        {start > 2 && <span className="px-1 text-surface-400">…</span>}
                    </>
                )}

                {pages.map(p => (
                    <PageBtn key={p} page={p} current={current_page} onClick={goTo} />
                ))}

                {end < last_page && (
                    <>
                        {end < last_page - 1 && <span className="px-1 text-surface-400">…</span>}
                        <PageBtn page={last_page} current={current_page} onClick={goTo} />
                    </>
                )}

                <button
                    onClick={() => goTo(current_page + 1)}
                    disabled={current_page === last_page}
                    className="p-1.5 rounded-button text-surface-700 hover:bg-surface-100 disabled:opacity-30 disabled:cursor-not-allowed"
                >
                    <ChevronRight size={16} />
                </button>
            </div>
        </div>
    );
}

function PageBtn({ page, current, onClick }) {
    const active = page === current;
    return (
        <button
            onClick={() => onClick(page)}
            className={`w-8 h-8 rounded-button text-sm font-medium transition-colors ${
                active
                    ? 'bg-primary-500 text-white'
                    : 'text-surface-700 hover:bg-surface-100'
            }`}
        >
            {page}
        </button>
    );
}
