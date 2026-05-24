const VARIANTS = {
    published:        'bg-emerald-50 text-emerald-700 border border-emerald-200',
    corrigendum:      'bg-amber-50 text-amber-700 border border-amber-200',
    cancelled:        'bg-red-50 text-red-600 border border-red-200',
    goods:            'bg-blue-50 text-blue-700 border border-blue-200',
    works:            'bg-orange-50 text-orange-700 border border-orange-200',
    consultancy:      'bg-purple-50 text-purple-700 border border-purple-200',
    'non-consultancy':'bg-indigo-50 text-indigo-700 border border-indigo-200',
    apply:            'bg-emerald-500 text-white',
    review:           'bg-amber-500 text-white',
    skip:             'bg-slate-200 text-slate-600',
    default:          'bg-surface-100 text-surface-700 border border-surface-200',
};

export default function Badge({ variant = 'default', children, className = '' }) {
    const cls = VARIANTS[variant] ?? VARIANTS.default;
    return (
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-badge text-xs font-semibold ${cls} ${className}`}>
            {children}
        </span>
    );
}
