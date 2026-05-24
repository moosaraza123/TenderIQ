import { Link } from '@inertiajs/react';
import { Building2, MapPin } from 'lucide-react';
import { StatusBadge, CategoryBadge } from './TenderBadge';
import CountryBadge from './CountryBadge';
import TenderDeadlineTimer from './TenderDeadlineTimer';
import TenderAiSummary from './TenderAiSummary';
import CurrencyDisplay from '@/Components/UI/CurrencyDisplay';
import { formatDate, truncate } from '@/lib/formatters';

export default function TenderCard({ tender, canViewSummary = false, index = 0 }) {
    return (
        <div
            className="bg-white border border-surface-200 rounded-card shadow-card hover:shadow-card-hover hover:border-primary-200 hover:-translate-y-px transition-all duration-200 p-5 animate-fade-in"
            style={{ animationDelay: `${index * 30}ms` }}
        >
            {/* Top row: badges + deadline */}
            <div className="flex items-center gap-2 mb-3 flex-wrap">
                {tender.country_code && tender.country_code !== 'PK' && (
                    <CountryBadge countryCode={tender.country_code} />
                )}
                {tender.category && <CategoryBadge category={tender.category} />}
                <StatusBadge status={tender.status} />
                <div className="ml-auto">
                    <TenderDeadlineTimer closingAt={tender.closing_at} />
                </div>
            </div>

            {/* Title */}
            <h3 className="text-tender-title text-surface-900 mb-1 line-clamp-2">
                {tender.title}
            </h3>

            {/* Org + ministry */}
            <p className="text-sm text-surface-400 mb-3 flex items-center gap-1">
                <Building2 size={12} />
                <span>{tender.organization_name}</span>
                {tender.ministry && (
                    <span className="text-surface-300"> · {truncate(tender.ministry, 40)}</span>
                )}
            </p>

            {/* AI Summary snippet */}
            {tender.ai_summary && (
                <div className="mb-3">
                    <TenderAiSummary
                        summary={tender.ai_summary}
                        canView={canViewSummary}
                        snippet
                    />
                </div>
            )}

            {/* Bottom row: city + budget + CTA */}
            <div className="flex items-center justify-between mt-2 pt-2 border-t border-surface-100">
                <div className="flex items-center gap-3 text-xs text-surface-400">
                    {tender.city && (
                        <span className="flex items-center gap-1">
                            <MapPin size={11} />
                            {tender.city}
                        </span>
                    )}
                    {(tender.budget || tender.ai_budget_extracted) && (
                        <CurrencyDisplay
                            amount={tender.budget ?? tender.ai_budget_extracted}
                            currency={tender.currency || 'PKR'}
                        />
                    )}
                    <span>Posted {formatDate(tender.advertised_at)}</span>
                </div>
                <Link
                    href={`/tenders/${tender.tender_number}`}
                    className="btn-primary text-xs py-1.5 px-3"
                >
                    View Details →
                </Link>
            </div>
        </div>
    );
}
