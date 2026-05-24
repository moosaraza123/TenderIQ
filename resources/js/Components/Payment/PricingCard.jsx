import { router } from '@inertiajs/react';

const PLANS = [
    {
        id:       'free',
        name:     'Free',
        price:    0,
        period:   '',
        tagline:  'Pakistan tenders — no credit card',
        color:    'border-surface-200',
        features: [
            '🇵🇰 Pakistan PPRA, SPPRA, KPPRA, BPPRA',
            '3,000+ tenders/month',
            '5 tender views per day',
            '1 keyword alert (daily digest)',
        ],
        cta:      'Start Free',
        ctaStyle: 'bg-surface-100 text-surface-800 hover:bg-surface-200',
    },
    {
        id:       'starter',
        name:     'Starter',
        price:    29,
        period:   '/mo',
        tagline:  'UK government tenders',
        color:    'border-primary-400',
        badge:    'Best for UK',
        features: [
            '🇵🇰 All Pakistan sources',
            '🇬🇧 UK Find a Tender (1,000+/week)',
            '🇬🇧 UK Contracts Finder (>£12k)',
            'Unlimited views + AI summaries',
            '5 keyword alerts (instant)',
            'CSV export',
        ],
        cta:      'Get Starter',
        ctaStyle: 'bg-primary-600 text-white hover:bg-primary-700',
    },
    {
        id:       'professional',
        name:     'Professional',
        price:    49,
        period:   '/mo',
        tagline:  'USA + World Bank + UN',
        color:    'border-amber-400',
        badge:    'Most Popular',
        features: [
            'Everything in Starter',
            '🇺🇸 SAM.gov USA (2,000+/day, $673B/yr)',
            '🌍 World Bank + UN + ADB + AfDB',
            '20 keyword alerts',
            'Budget range alerts',
            'Daily/weekly digest',
        ],
        cta:      'Get Professional',
        ctaStyle: 'bg-amber-500 text-white hover:bg-amber-600',
    },
    {
        id:       'enterprise',
        name:     'Enterprise',
        price:    99,
        period:   '/mo',
        tagline:  'API access + team features',
        color:    'border-violet-400',
        features: [
            'Everything in Professional',
            'Unlimited alerts',
            'API access (1,000 calls/day)',
            'Webhooks (POST on match)',
            'Priority email support',
            'Custom alert workflows',
        ],
        cta:      'Get Enterprise',
        ctaStyle: 'bg-violet-600 text-white hover:bg-violet-700',
    },
];

export default function PricingCard({ userPlan = 'free', isAuthenticated = false }) {
    const handleSelect = (planId) => {
        if (planId === 'free') return;
        if (!isAuthenticated) {
            router.visit('/register');
            return;
        }
        router.post('/subscription/checkout', { plan: planId });
    };

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {PLANS.map(plan => (
                <div
                    key={plan.id}
                    className={`relative rounded-2xl border-2 p-6 flex flex-col gap-4 bg-white shadow-sm ${plan.color}`}
                >
                    {plan.badge && (
                        <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-amber-500 text-white text-xs font-bold px-3 py-0.5 rounded-full">
                            {plan.badge}
                        </span>
                    )}

                    <div>
                        <h3 className="text-lg font-bold text-surface-900">{plan.name}</h3>
                        <p className="text-sm text-surface-500">{plan.tagline}</p>
                    </div>

                    <div className="text-3xl font-extrabold text-surface-900">
                        {plan.price === 0 ? 'Free' : `$${plan.price}`}
                        {plan.period && <span className="text-base font-normal text-surface-500">{plan.period}</span>}
                    </div>

                    <ul className="flex-1 space-y-2 text-sm text-surface-700">
                        {plan.features.map((f, i) => (
                            <li key={i} className="flex items-start gap-1.5">
                                <span className="text-green-500 mt-0.5 shrink-0">✓</span>
                                <span>{f}</span>
                            </li>
                        ))}
                    </ul>

                    <button
                        onClick={() => handleSelect(plan.id)}
                        disabled={userPlan === plan.id}
                        className={`w-full rounded-lg py-2.5 font-semibold text-sm transition-colors disabled:opacity-50 disabled:cursor-default ${plan.ctaStyle}`}
                    >
                        {userPlan === plan.id ? 'Current Plan' : plan.cta}
                    </button>
                </div>
            ))}
        </div>
    );
}
