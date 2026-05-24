import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import TenderCard from '@/Components/Tender/TenderCard';
import SearchBar from '@/Components/Search/SearchBar';
import PricingCard from '@/Components/Payment/PricingCard';
import { Zap, Bell, Globe2, Shield } from 'lucide-react';

const SOURCES = [
    { flag: '🇵🇰', label: 'PPRA Federal' },
    { flag: '🇵🇰', label: 'SPPRA Sindh' },
    { flag: '🇵🇰', label: 'KPPRA KPK' },
    { flag: '🇵🇰', label: 'BPPRA Baloch' },
    { flag: '🇬🇧', label: 'UK Find a Tender' },
    { flag: '🇬🇧', label: 'UK Contracts Finder' },
    { flag: '🇺🇸', label: 'SAM.gov USA' },
    { flag: '🌍', label: 'World Bank' },
    { flag: '🌍', label: 'UN (UNGM)' },
    { flag: '🌍', label: 'ADB' },
    { flag: '🌍', label: 'AfDB' },
];

const MARKET_CARDS = [
    { flag: '🇺🇸', name: 'USA', source: 'SAM.gov', price: '$49/mo', volume: '2,000+/day', color: 'border-blue-200 bg-blue-50' },
    { flag: '🇬🇧', name: 'UK',  source: 'Gov FTS', price: '$29/mo', volume: '1,000+/week', color: 'border-red-200 bg-red-50' },
    { flag: '🌍', name: 'World Bank', source: 'WB API', price: '$49/mo', volume: '500+/mo', color: 'border-green-200 bg-green-50' },
    { flag: '🇵🇰', name: 'Pakistan',  source: 'PPRA',   price: 'Free',    volume: '3,000+/mo', color: 'border-surface-200 bg-surface-50' },
];

export default function Home({ recentTenders = [], stats = {}, userPlan = 'free', isAuthenticated = false }) {
    const [search, setSearch] = useState('');

    function handleSearch(value) {
        router.get('/tenders', value ? { keyword: value } : {});
    }

    return (
        <AppLayout>
            <Head title="Find Government Tenders from UK, USA & Global Markets | TenderIQ" />

            {/* Hero */}
            <section className="min-h-[480px] bg-gradient-to-br from-surface-50 via-white to-primary-50/30 flex items-center relative overflow-hidden">
                <div
                    className="absolute inset-0 opacity-40"
                    style={{
                        backgroundImage: 'radial-gradient(circle, #e2e8f0 1px, transparent 1px)',
                        backgroundSize: '24px 24px',
                    }}
                />
                <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center w-full">
                    <div className="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-semibold px-3 py-1.5 rounded-full mb-6 border border-primary-100">
                        <Zap size={12} />
                        AI-Powered · 11 Sources · UK + USA + World Bank + Pakistan
                    </div>
                    <h1 className="text-4xl md:text-5xl font-bold text-surface-900 tracking-tight mb-4 max-w-3xl mx-auto">
                        Find Government Tenders from{' '}
                        <span className="text-primary-500">UK, USA & Global Markets</span>
                    </h1>
                    <p className="text-lg text-surface-700 mb-8 max-w-2xl mx-auto">
                        AI-powered summaries. Instant alerts. 10,000+ tenders from USA SAM.gov,
                        UK government, World Bank and Pakistan PPRA.
                    </p>
                    <div className="max-w-2xl mx-auto mb-6">
                        <SearchBar
                            value={search}
                            onChange={setSearch}
                            onSubmit={handleSearch}
                            size="hero"
                        />
                    </div>
                    <div className="flex flex-wrap items-center justify-center gap-3 text-sm text-surface-500">
                        <span className="flex items-center gap-1"><Shield size={13} /> Free Pakistan access</span>
                        <span>·</span>
                        <Link href="/tenders/uk" className="hover:text-primary-600">🇬🇧 UK from $29/mo</Link>
                        <span>·</span>
                        <Link href="/tenders/usa" className="hover:text-primary-600">🇺🇸 USA from $49/mo</Link>
                        <span>·</span>
                        <span className="text-amber-600 font-medium">GovWin charges $2,000/mo — we charge $49</span>
                    </div>
                </div>
            </section>

            {/* Stats bar */}
            <section className="border-y border-surface-100 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-wrap justify-center gap-8">
                    {[
                        { value: stats.total_tenders?.toLocaleString() ?? '10,000+', label: 'Active Tenders' },
                        { value: stats.open_tenders?.toLocaleString() ?? '—', label: 'Open Now' },
                        { value: '11', label: 'Data Sources' },
                        { value: 'USA + UK + WB + PK', label: 'Markets' },
                    ].map(s => (
                        <div key={s.label} className="text-center">
                            <div className="text-2xl font-bold text-primary-600">{s.value}</div>
                            <div className="text-xs text-surface-400">{s.label}</div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Market showcase */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <h2 className="text-xl font-bold text-surface-900 text-center mb-6">
                    Four markets. One platform.
                </h2>
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    {MARKET_CARDS.map(m => (
                        <div key={m.name} className={`rounded-xl border-2 p-5 text-center ${m.color}`}>
                            <div className="text-3xl mb-2">{m.flag}</div>
                            <div className="font-bold text-surface-900">{m.name}</div>
                            <div className="text-xs text-surface-500 mb-2">{m.source}</div>
                            <div className="text-sm font-semibold text-primary-600">{m.volume}</div>
                            <div className="text-xs text-surface-400 mt-1">{m.price}</div>
                        </div>
                    ))}
                </div>
            </section>

            {/* Source logos */}
            <section className="bg-surface-50 py-8">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p className="text-xs font-semibold text-surface-400 text-center mb-4 uppercase tracking-wider">
                        Data from 11 official procurement portals
                    </p>
                    <div className="flex flex-wrap justify-center gap-3">
                        {SOURCES.map(s => (
                            <span key={s.label} className="flex items-center gap-1.5 bg-white border border-surface-200 rounded-full px-3 py-1.5 text-xs text-surface-600 font-medium">
                                {s.flag} {s.label}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            {/* Recent tenders */}
            {recentTenders.length > 0 && (
                <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-xl font-bold text-surface-900">Latest Tenders</h2>
                        <Link href="/tenders" className="text-sm text-primary-600 hover:underline">
                            Browse all →
                        </Link>
                    </div>
                    <div className="flex flex-col gap-3">
                        {recentTenders.slice(0, 6).map((t, i) => (
                            <TenderCard key={t.id} tender={t} index={i} />
                        ))}
                    </div>
                    <div className="text-center mt-8">
                        <Link href="/tenders" className="btn-primary inline-block">
                            Browse All Tenders
                        </Link>
                    </div>
                </section>
            )}

            {/* Why TenderIQ */}
            <section className="bg-surface-50 py-16">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 className="text-2xl font-bold text-surface-900 text-center mb-10">
                        Why TenderIQ?
                    </h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {[
                            { icon: <Zap size={20} />,   title: 'AI Summaries',     desc: 'AI reads the 40-page RFP. You read the 3-line summary.' },
                            { icon: <Bell size={20} />,  title: 'Smart Alerts',     desc: 'Set a keyword. Never check SAM.gov or FTS again.' },
                            { icon: <Globe2 size={20} />, title: '1/20th the price', desc: 'GovWin costs $2,000/month. TenderIQ costs $49.' },
                            { icon: <Shield size={20} />, title: 'Free Pakistan',    desc: 'PPRA tenders always free — no credit card required.' },
                        ].map(f => (
                            <div key={f.title} className="bg-white rounded-xl border border-surface-100 p-5">
                                <div className="w-9 h-9 bg-primary-50 text-primary-600 rounded-lg flex items-center justify-center mb-3">
                                    {f.icon}
                                </div>
                                <h3 className="font-semibold text-surface-900 mb-1">{f.title}</h3>
                                <p className="text-sm text-surface-500">{f.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h2 className="text-2xl font-bold text-surface-900 text-center mb-2">
                    Start free. Scale as you grow.
                </h2>
                <p className="text-center text-surface-500 mb-10 text-sm">
                    Cancel anytime. No annual commitment.
                </p>
                <PricingCard userPlan={userPlan} isAuthenticated={isAuthenticated} />
            </section>
        </AppLayout>
    );
}
