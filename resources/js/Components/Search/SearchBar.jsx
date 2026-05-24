import { Search } from 'lucide-react';

export default function SearchBar({ value, onChange, onSubmit, placeholder = 'Search tenders by keyword, organization, sector…', size = 'default' }) {
    const isHero = size === 'hero';

    function handleKey(e) {
        if (e.key === 'Enter' && onSubmit) onSubmit(value);
    }

    return (
        <div className={`relative flex items-center ${isHero ? 'shadow-lg' : 'shadow-sm'}`}>
            <Search
                size={isHero ? 20 : 16}
                className="absolute left-4 text-surface-400 pointer-events-none"
            />
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                onKeyDown={handleKey}
                placeholder={placeholder}
                className={`
                    w-full pl-11 pr-4 bg-white border-2 border-surface-200 rounded-2xl
                    text-surface-800 placeholder-surface-300
                    focus:outline-none focus:border-primary-400 focus:ring-4 focus:ring-primary-500/10
                    transition-all duration-150
                    ${isHero ? 'h-14 text-base pr-36' : 'h-10 text-sm'}
                `}
            />
            {isHero && (
                <button
                    onClick={() => onSubmit?.(value)}
                    className="absolute right-1.5 my-1.5 bg-primary-500 hover:bg-primary-600 text-white rounded-xl px-5 py-2 text-sm font-medium transition-colors"
                >
                    Search
                </button>
            )}
        </div>
    );
}
