import { COUNTRIES } from '@/lib/constants';

export default function CountryTabs({ activeCountry, onChange }) {
    const tabs = [
        { code: null, name: 'All', flag: '🌐' },
        ...COUNTRIES,
    ];

    return (
        <div className="flex items-center gap-1 overflow-x-auto pb-1">
            {tabs.map(tab => (
                <button
                    key={tab.code ?? 'all'}
                    onClick={() => onChange(tab.code)}
                    className={[
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors',
                        activeCountry === tab.code
                            ? 'bg-primary-600 text-white'
                            : 'bg-surface-100 text-surface-700 hover:bg-surface-200',
                    ].join(' ')}
                >
                    <span>{tab.flag}</span>
                    <span>{tab.name}</span>
                </button>
            ))}
        </div>
    );
}
