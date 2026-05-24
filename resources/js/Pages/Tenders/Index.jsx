import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import TenderCard from '@/Components/Tender/TenderCard';
import FilterSidebar from '@/Components/Search/FilterSidebar';
import CountryTabs from '@/Components/Search/CountryTabs';
import Pagination from '@/Components/UI/Pagination';
import EmptyState from '@/Components/UI/EmptyState';
import LoadingSkeleton from '@/Components/UI/LoadingSkeleton';
import { useTenderSearch } from '@/hooks/useTenderSearch';
import { useSubscription } from '@/hooks/useSubscription';
import { SlidersHorizontal } from 'lucide-react';

export default function Index({ tenders, filters: initialFilters = {}, stats = {} }) {
    const { filters, setFilter, reset, activeCount } = useTenderSearch(initialFilters);
    const { canViewSummary } = useSubscription();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const sortOptions = [
        { value: 'closing_soon', label: 'Closing soon' },
        { value: 'newest',       label: 'Newest first' },
    ];

    return (
        <AppLayout>
            <Head title="Browse Pakistan Government Tenders" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Country Tabs */}
                <div className="mb-5">
                    <CountryTabs
                        activeCountry={filters.country ?? null}
                        onChange={(code) => setFilter('country', code)}
                    />
                </div>

                {/* Page header */}
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-surface-900">Government Tenders</h1>
                        <p className="text-sm text-surface-400 mt-1">
                            Showing {tenders?.total?.toLocaleString() ?? 0} tenders
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        {/* Sort */}
                        <select
                            value={filters.sort}
                            onChange={(e) => setFilter('sort', e.target.value)}
                            className="text-sm border border-surface-200 rounded-button px-3 py-2 text-surface-700 bg-white focus:outline-none focus:border-primary-400"
                        >
                            {sortOptions.map(o => (
                                <option key={o.value} value={o.value}>{o.label}</option>
                            ))}
                        </select>

                        {/* Mobile filter toggle */}
                        <button
                            onClick={() => setSidebarOpen(true)}
                            className="md:hidden btn-secondary flex items-center gap-2"
                        >
                            <SlidersHorizontal size={14} />
                            Filters {activeCount > 0 && `(${activeCount})`}
                        </button>
                    </div>
                </div>

                <div className="flex gap-6">
                    {/* Desktop sidebar */}
                    <FilterSidebar
                        filters={filters}
                        onFilterChange={setFilter}
                        onReset={reset}
                        activeCount={activeCount}
                        className="hidden md:block w-64 shrink-0 sticky top-20 self-start max-h-[calc(100vh-6rem)] overflow-y-auto rounded-card"
                    />

                    {/* Main content */}
                    <div className="flex-1 min-w-0">
                        {!tenders?.data?.length ? (
                            <EmptyState action={reset} />
                        ) : (
                            <div className="flex flex-col gap-3">
                                {tenders.data.map((tender, i) => (
                                    <TenderCard
                                        key={tender.id}
                                        tender={tender}
                                        canViewSummary={canViewSummary}
                                        index={i}
                                    />
                                ))}
                            </div>
                        )}

                        <Pagination
                            meta={tenders?.meta ?? tenders}
                            baseUrl="/tenders"
                            filters={filters}
                        />
                    </div>
                </div>
            </div>

            {/* Mobile drawer */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-50 md:hidden">
                    <div className="absolute inset-0 bg-black/40" onClick={() => setSidebarOpen(false)} />
                    <div className="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl max-h-[85vh] overflow-y-auto animate-slide-up">
                        <div className="p-2 flex justify-center">
                            <div className="w-10 h-1 bg-surface-200 rounded-full" />
                        </div>
                        <FilterSidebar
                            filters={filters}
                            onFilterChange={(k, v) => { setFilter(k, v); setSidebarOpen(false); }}
                            onReset={() => { reset(); setSidebarOpen(false); }}
                            activeCount={activeCount}
                            className="border-r-0"
                        />
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
