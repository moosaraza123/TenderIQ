import { formatCurrency } from '@/lib/formatters';

export default function CurrencyDisplay({ amount, currency = 'PKR', className = '' }) {
    const formatted = formatCurrency(amount, currency);
    if (!formatted) return null;

    return (
        <span className={`text-sm font-medium text-surface-700 ${className}`}>
            {formatted}
        </span>
    );
}
