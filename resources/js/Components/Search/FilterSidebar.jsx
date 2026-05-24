import { SlidersHorizontal, X } from 'lucide-react';
import { CATEGORIES, SECTORS, CITIES, TENDER_TYPES } from '@/lib/constants';
import SearchBar from './SearchBar';

function FilterSection({ label, children }) {
    return (
        <div className="mb-5">
            <p className="text-xs uppercase tracking-widest text-surface-400 font-semibold mb-2">
                {label}
            </p>
            {children}
        </div>
    );
}

function SelectFilter({ value, onChange, options, placeholder }) {
    return (
        <select
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 text-surface-700 bg-white focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
        >
            <option value="">{placeholder}</option>
            {options.map((opt) => (
                <option key={opt} value={opt}>{opt}</option>
            ))}
        </select>
    );
}

export default function FilterSidebar({ filters, onFilterChange, onReset, activeCount = 0, className = '' }) {
    return (
        <aside className={`bg-white border-r border-surface-200 p-5 ${className}`}>
            <div className="flex items-center justify-between mb-5">
                <div className="flex items-center gap-2">
                    <SlidersHorizontal size={16} className="text-surface-700" />
                    <span className="text-sm font-semibold text-surface-800">Filters</span>
                    {activeCount > 0 && (
                        <span className="bg-primary-500 text-white text-xs rounded-full px-1.5 py-0.5 font-semibold">
                            {activeCount}
                        </span>
                    )}
                </div>
                {activeCount > 0 && (
                    <button onClick={onReset} className="text-xs text-surface-400 hover:text-primary-600 flex items-center gap-1">
                        <X size={12} /> Clear
                    </button>
                )}
            </div>

            <FilterSection label="Search">
                <SearchBar
                    value={filters.keyword ?? ''}
                    onChange={(v) => onFilterChange('keyword', v)}
                    placeholder="Keywords…"
                />
            </FilterSection>

            <FilterSection label="Category">
                <SelectFilter
                    value={filters.category ?? ''}
                    onChange={(v) => onFilterChange('category', v)}
                    options={CATEGORIES}
                    placeholder="All categories"
                />
            </FilterSection>

            <FilterSection label="Sector">
                <SelectFilter
                    value={filters.sector ?? ''}
                    onChange={(v) => onFilterChange('sector', v)}
                    options={SECTORS}
                    placeholder="All sectors"
                />
            </FilterSection>

            <FilterSection label="City">
                <SelectFilter
                    value={filters.city ?? ''}
                    onChange={(v) => onFilterChange('city', v)}
                    options={CITIES}
                    placeholder="All cities"
                />
            </FilterSection>

            <FilterSection label="Status">
                <SelectFilter
                    value={filters.status ?? ''}
                    onChange={(v) => onFilterChange('status', v)}
                    options={['Published', 'Corrigendum', 'Cancelled']}
                    placeholder="Any status"
                />
            </FilterSection>

            <FilterSection label="Tender Type">
                <SelectFilter
                    value={filters.tender_type ?? ''}
                    onChange={(v) => onFilterChange('tender_type', v)}
                    options={TENDER_TYPES}
                    placeholder="Any type"
                />
            </FilterSection>

            <FilterSection label="Closing Date">
                <div className="flex flex-col gap-2">
                    <input
                        type="date"
                        value={filters.closing_from ?? ''}
                        onChange={(e) => onFilterChange('closing_from', e.target.value)}
                        className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 text-surface-700 focus:outline-none focus:border-primary-400"
                        placeholder="From"
                    />
                    <input
                        type="date"
                        value={filters.closing_to ?? ''}
                        onChange={(e) => onFilterChange('closing_to', e.target.value)}
                        className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 text-surface-700 focus:outline-none focus:border-primary-400"
                        placeholder="To"
                    />
                </div>
            </FilterSection>
        </aside>
    );
}
