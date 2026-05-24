import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { StatusBadge, CategoryBadge, RecommendationBadge } from '@/Components/Tender/TenderBadge';
import CountryBadge from '@/Components/Tender/CountryBadge';
import TenderDeadlineTimer from '@/Components/Tender/TenderDeadlineTimer';
import TenderAiSummary from '@/Components/Tender/TenderAiSummary';
import TenderPdfList from '@/Components/Tender/TenderPdfList';
import LockedOverlay from '@/Components/UI/LockedOverlay';
import CurrencyDisplay from '@/Components/UI/CurrencyDisplay';
import { Building2, MapPin, Calendar, ChevronLeft, Share2, Bell } from 'lucide-react';
import { formatDate, formatDateTime } from '@/lib/formatters';
import { useSubscription } from '@/hooks/useSubscription';

const PLAN_NAMES = { basic: 'Basic ($49/mo)', pro: 'Pro ($99/mo)', enterprise: 'Enterprise ($199/mo)' };

const SOURCE_LABELS = {
    ppra_federal: 'PPRA',
    sppra:        'SPPRA',
    kppra:        'KPPRA',
    bppra:        'BPPRA',
    sam_gov:      'SAM.gov',
    world_bank:   'World Bank',
    ungm:         'UN (UNGM)',
    adb:          'ADB',
    afdb:         'AfDB',
    uk_fts:       'UK Find a Tender',
    uk_cf:        'UK Contracts Finder',
};

function sourceLabel(source) {
    return SOURCE_LABELS[source] ?? source?.replace(/_/g, ' ') ?? 'Source';
}

export default function Show({
    tender,
    accessDenied = false,
    tierLocked = false,
    requiredPlan = null,
    canViewSummary = false,
    canDownloadPdf = false,
    canViewRec = false,
    seoTitle,
    seoDescription,
    viewsUsed,
    viewLimit,
}) {
    const { isLoggedIn } = useSubscription();

    if (accessDenied && tierLocked) {
        return (
            <AppLayout>
                <Head title="Upgrade Required" />
                <div className="max-w-lg mx-auto px-4 py-20 text-center">
                    <div className="text-5xl mb-4">🔒</div>
                    <h1 className="text-2xl font-bold text-surface-900 mb-3">
                        {tender?.country_code === 'AE' ? '🇦🇪 UAE' : tender?.country_code === 'SA' ? '🇸🇦 Saudi Arabia' : '🌍 International'} Tender
                    </h1>
                    <p className="text-surface-600 mb-6">
                        Access to this tender requires the <strong>{PLAN_NAMES[requiredPlan] ?? 'paid'}</strong> plan.
                    </p>
                    <div className="flex flex-col sm:flex-row gap-3 justify-center">
                        <Link href="/pricing" className="btn-primary inline-block">View Pricing</Link>
                        <Link href="/tenders" className="btn-secondary inline-block">Back to Tenders</Link>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (accessDenied) {
        return (
            <AppLayout>
                <Head title="Daily Limit Reached" />
                <div className="max-w-lg mx-auto px-4 py-20 text-center">
                    <h1 className="text-2xl font-bold text-surface-900 mb-3">Daily view limit reached</h1>
                    <p className="text-surface-400 mb-6">
                        You've used {viewsUsed} of {viewLimit} free tender views today.
                        Upgrade for unlimited access.
                    </p>
                    <Link href="/pricing" className="btn-primary inline-block">Upgrade now</Link>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head>
                <title>{seoTitle ?? tender.title}</title>
                <meta name="description" content={seoDescription ?? ''} />
                <meta property="og:title" content={seoTitle ?? tender.title} />
            </Head>

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Breadcrumb */}
                <div className="flex items-center gap-2 text-xs text-surface-400 mb-6">
                    <Link href="/" className="hover:text-primary-600">Home</Link>
                    <span>›</span>
                    <Link href="/tenders" className="hover:text-primary-600">Tenders</Link>
                    <span>›</span>
                    <span className="text-surface-600">{tender.tender_number}</span>
                </div>

                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Main content */}
                    <div className="flex-1 min-w-0">
                        {/* Back */}
                        <Link href="/tenders" className="inline-flex items-center gap-1 btn-ghost text-sm mb-4 -ml-2">
                            <ChevronLeft size={14} /> Back to tenders
                        </Link>

                        {/* Badges */}
                        <div className="flex items-center gap-2 flex-wrap mb-3">
                            {tender.category && <CategoryBadge category={tender.category} />}
                            <StatusBadge status={tender.status} />
                            {tender.tender_type && (
                                <span className="text-xs text-surface-400 font-medium px-2 py-0.5 border border-surface-200 rounded-badge">
                                    {tender.tender_type}
                                </span>
                            )}
                        </div>

                        <h1 className="text-2xl font-bold text-surface-900 tracking-tight mb-2">
                            {tender.title}
                        </h1>

                        <div className="flex flex-wrap items-center gap-4 text-sm text-surface-400 mb-6">
                            <span className="flex items-center gap-1">
                                <Building2 size={14} />
                                {tender.organization_name}
                            </span>
                            {tender.country_code && (
                                <CountryBadge countryCode={tender.country_code} />
                            )}
                            {tender.city && (
                                <span className="flex items-center gap-1">
                                    <MapPin size={14} />
                                    {tender.city}
                                </span>
                            )}
                            <span className="flex items-center gap-1">
                                <Calendar size={14} />
                                Posted {formatDate(tender.advertised_at)}
                            </span>
                        </div>

                        {/* AI Summary */}
                        {tender.ai_summary && (
                            <div className="mb-6">
                                <h2 className="text-sm font-semibold text-surface-800 mb-3">AI Summary</h2>
                                <div className="bg-surface-50 rounded-card p-4">
                                    <TenderAiSummary summary={tender.ai_summary} canView={canViewSummary} />
                                </div>
                            </div>
                        )}

                        {/* Eligibility */}
                        {tender.ai_eligibility && (
                            <div className="mb-6">
                                <h2 className="text-sm font-semibold text-surface-800 mb-3">Eligibility Requirements</h2>
                                <LockedOverlay
                                    isLocked={!canViewSummary}
                                    message="Upgrade to view eligibility requirements"
                                    plan="basic"
                                >
                                    <div className="bg-surface-50 rounded-card p-4 text-sm text-surface-700 leading-relaxed whitespace-pre-line">
                                        {tender.ai_eligibility}
                                    </div>
                                </LockedOverlay>
                            </div>
                        )}

                        {/* Description */}
                        {tender.description && (
                            <div className="mb-6">
                                <h2 className="text-sm font-semibold text-surface-800 mb-3">Description</h2>
                                <div className="text-sm text-surface-700 leading-relaxed whitespace-pre-line">
                                    {tender.description}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Sidebar */}
                    <aside className="lg:w-72 shrink-0 lg:sticky lg:top-24 self-start flex flex-col gap-4">
                        {/* Deadline card */}
                        <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
                            <p className="text-xs text-surface-400 font-semibold uppercase tracking-widest mb-2">
                                Closing deadline
                            </p>
                            <p className="text-lg font-bold text-surface-900 mb-1">
                                {formatDateTime(tender.closing_at)}
                            </p>
                            <TenderDeadlineTimer closingAt={tender.closing_at} showLabel />
                        </div>

                        {/* AI Recommendation */}
                        {tender.ai_recommendation && (
                            <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
                                <p className="text-xs text-surface-400 font-semibold uppercase tracking-widest mb-3">
                                    AI Recommendation
                                </p>
                                <LockedOverlay
                                    isLocked={!canViewRec}
                                    message="Pro plan required for AI recommendations"
                                    plan="pro"
                                >
                                    <RecommendationBadge recommendation={tender.ai_recommendation} />
                                </LockedOverlay>
                            </div>
                        )}

                        {/* Quick facts */}
                        <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
                            <p className="text-xs text-surface-400 font-semibold uppercase tracking-widest mb-3">
                                Quick facts
                            </p>
                            <dl className="flex flex-col gap-2 text-sm">
                                <Row label="Tender #" value={tender.tender_number} />
                                {tender.ministry && <Row label="Ministry" value={tender.ministry} />}
                                {tender.sector && <Row label="Sector" value={tender.sector} />}
                                {(tender.budget || tender.ai_budget_extracted) && (
                                    <Row label="Budget" value={
                                        <CurrencyDisplay amount={tender.budget ?? tender.ai_budget_extracted} currency={tender.currency || 'PKR'} />
                                    } />
                                )}
                            </dl>
                        </div>

                        {/* PDF list */}
                        {tender.pdf_urls?.length > 0 && (
                            <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
                                <p className="text-xs text-surface-400 font-semibold uppercase tracking-widest mb-3">
                                    Documents
                                </p>
                                <TenderPdfList pdfUrls={tender.pdf_urls} canDownload={canDownloadPdf} />
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex flex-col gap-2">
                            {isLoggedIn && (
                                <Link href="/alerts" className="btn-secondary flex items-center justify-center gap-2">
                                    <Bell size={14} />
                                    Set alert for similar tenders
                                </Link>
                            )}
                            <button
                                onClick={() => navigator.share?.({ title: tender.title, url: window.location.href })}
                                className="btn-secondary flex items-center justify-center gap-2"
                            >
                                <Share2 size={14} />
                                Share
                            </button>
                            <a
                                href={tender.detail_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn-secondary text-center text-sm"
                            >
                                View on {sourceLabel(tender.source)} →
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}

function Row({ label, value }) {
    return (
        <div className="flex justify-between gap-2">
            <dt className="text-surface-400">{label}</dt>
            <dd className="text-surface-700 font-medium text-right">{value}</dd>
        </div>
    );
}
