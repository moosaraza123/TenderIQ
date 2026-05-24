import { COUNTRIES } from '@/lib/constants';

export default function CountryBadge({ countryCode, className = '' }) {
    const country = COUNTRIES.find(c => c.code === countryCode);
    if (!country) return null;

    return (
        <span className={`inline-flex items-center gap-1 text-xs font-medium ${className}`}>
            <span>{country.flag}</span>
            <span>{country.name}</span>
        </span>
    );
}
