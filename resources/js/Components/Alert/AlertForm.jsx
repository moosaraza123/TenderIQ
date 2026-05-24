import { useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { X, Plus } from 'lucide-react';
import { CATEGORIES, CITIES } from '@/lib/constants';

export default function AlertForm({ onSuccess }) {
    const { auth } = usePage().props;
    const plan = auth?.user?.plan ?? 'free';
    const isFree = plan === 'free';

    const { data, setData, post, processing, errors, reset } = useForm({
        keywords:   [],
        categories: [],
        cities:     [],
        min_budget: '',
        max_budget: '',
        frequency:  isFree ? 'daily' : 'instant',
    });

    const [kwInput, setKwInput] = useState('');

    function addKeyword() {
        const kw = kwInput.trim();
        if (!kw || data.keywords.includes(kw)) return;
        setData('keywords', [...data.keywords, kw]);
        setKwInput('');
    }

    function removeKeyword(kw) {
        setData('keywords', data.keywords.filter(k => k !== kw));
    }

    function toggleArray(field, value) {
        const arr = data[field];
        setData(field, arr.includes(value) ? arr.filter(v => v !== value) : [...arr, value]);
    }

    function submit(e) {
        e.preventDefault();
        post('/alerts', { onSuccess: () => { reset(); onSuccess?.(); } });
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            {/* Keywords */}
            <div>
                <label className="text-xs uppercase tracking-widest text-surface-400 font-semibold block mb-2">
                    Keywords <span className="text-red-500">*</span>
                </label>
                <div className="flex gap-2 mb-2">
                    <input
                        value={kwInput}
                        onChange={(e) => setKwInput(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addKeyword())}
                        placeholder="Add keyword…"
                        className="flex-1 text-sm border border-surface-200 rounded-button px-3 py-2 focus:outline-none focus:border-primary-400"
                    />
                    <button type="button" onClick={addKeyword} className="btn-secondary p-2">
                        <Plus size={14} />
                    </button>
                </div>
                <div className="flex flex-wrap gap-2">
                    {data.keywords.map(kw => (
                        <span key={kw} className="inline-flex items-center gap-1 bg-primary-50 text-primary-700 text-xs px-2 py-1 rounded-badge">
                            {kw}
                            <button type="button" onClick={() => removeKeyword(kw)}><X size={10} /></button>
                        </span>
                    ))}
                </div>
                {errors.keywords && <p className="text-red-500 text-xs mt-1">{errors.keywords}</p>}
            </div>

            {/* Categories */}
            <div>
                <label className="text-xs uppercase tracking-widest text-surface-400 font-semibold block mb-2">Categories (optional)</label>
                <div className="flex flex-wrap gap-2">
                    {CATEGORIES.map(cat => (
                        <button
                            key={cat}
                            type="button"
                            onClick={() => toggleArray('categories', cat)}
                            className={`text-xs px-2.5 py-1 rounded-badge border transition-colors ${
                                data.categories.includes(cat)
                                    ? 'bg-primary-500 text-white border-primary-500'
                                    : 'bg-white text-surface-700 border-surface-200 hover:border-primary-300'
                            }`}
                        >
                            {cat}
                        </button>
                    ))}
                </div>
            </div>

            {/* Cities */}
            <div>
                <label className="text-xs uppercase tracking-widest text-surface-400 font-semibold block mb-2">Cities (optional)</label>
                <select
                    multiple
                    value={data.cities}
                    onChange={(e) => setData('cities', Array.from(e.target.selectedOptions, o => o.value))}
                    className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 h-28 focus:outline-none focus:border-primary-400"
                >
                    {CITIES.map(city => (
                        <option key={city} value={city}>{city}</option>
                    ))}
                </select>
            </div>

            {/* Budget range */}
            <div className="flex gap-3">
                <div className="flex-1">
                    <label className="text-xs text-surface-400 block mb-1">Min Budget (PKR)</label>
                    <input
                        type="number"
                        value={data.min_budget}
                        onChange={(e) => setData('min_budget', e.target.value)}
                        placeholder="0"
                        className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 focus:outline-none focus:border-primary-400"
                    />
                </div>
                <div className="flex-1">
                    <label className="text-xs text-surface-400 block mb-1">Max Budget (PKR)</label>
                    <input
                        type="number"
                        value={data.max_budget}
                        onChange={(e) => setData('max_budget', e.target.value)}
                        placeholder="No limit"
                        className="w-full text-sm border border-surface-200 rounded-button px-3 py-2 focus:outline-none focus:border-primary-400"
                    />
                </div>
            </div>

            {/* Frequency */}
            <div>
                <label className="text-xs uppercase tracking-widest text-surface-400 font-semibold block mb-2">
                    Alert frequency
                </label>
                {isFree ? (
                    <div className="flex items-center gap-2 text-sm text-surface-500 bg-surface-50 border border-surface-200 rounded-button px-3 py-2">
                        <span>Daily digest</span>
                        <span className="text-xs text-surface-400 ml-auto">Upgrade for instant alerts</span>
                    </div>
                ) : (
                    <div className="flex gap-2">
                        {['instant', 'daily', 'weekly'].map(f => (
                            <button
                                key={f}
                                type="button"
                                onClick={() => setData('frequency', f)}
                                className={`flex-1 text-xs px-3 py-2 rounded-button border capitalize transition-colors ${
                                    data.frequency === f
                                        ? 'bg-primary-500 text-white border-primary-500'
                                        : 'bg-white text-surface-700 border-surface-200 hover:border-primary-300'
                                }`}
                            >
                                {f}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <button type="submit" disabled={processing || data.keywords.length === 0} className="btn-primary w-full">
                {processing ? 'Creating…' : 'Create Alert'}
            </button>
        </form>
    );
}
