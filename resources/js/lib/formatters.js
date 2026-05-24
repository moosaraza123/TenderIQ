export function formatDate(date) {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-PK', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}

export function formatDateTime(date) {
    if (!date) return '';
    return new Date(date).toLocaleString('en-PK', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

export function formatPKR(amount) {
    if (amount == null) return null;
    const num = parseFloat(amount);
    if (isNaN(num)) return null;

    if (num >= 1_000_000_000) return `PKR ${(num / 1_000_000_000).toFixed(1)}B`;
    if (num >= 1_000_000)     return `PKR ${(num / 1_000_000).toFixed(1)}M`;
    if (num >= 1_000)         return `PKR ${(num / 1_000).toFixed(0)}K`;
    return `PKR ${num.toLocaleString('en-PK')}`;
}

// PKR/USD approximate exchange rates for display only
const TO_PKR = { AED: 75, SAR: 73, USD: 278, PKR: 1 };

export function formatCurrency(amount, currency = 'PKR') {
    if (amount == null) return null;
    const num = parseFloat(amount);
    if (isNaN(num)) return null;

    const sym = currency || 'PKR';

    const fmt = (n) => {
        if (n >= 1_000_000_000) return `${sym} ${(n / 1_000_000_000).toFixed(1)}B`;
        if (n >= 1_000_000)     return `${sym} ${(n / 1_000_000).toFixed(1)}M`;
        if (n >= 1_000)         return `${sym} ${(n / 1_000).toFixed(0)}K`;
        return `${sym} ${n.toLocaleString()}`;
    };

    const primary = fmt(num);

    if (sym !== 'PKR' && TO_PKR[sym]) {
        const pkr = num * TO_PKR[sym];
        const pkrFmt = formatPKR(pkr);
        return pkrFmt ? `${primary} (≈ ${pkrFmt})` : primary;
    }

    return primary;
}

export function truncate(text, length = 120) {
    if (!text) return '';
    if (text.length <= length) return text;
    return text.slice(0, length).trimEnd() + '…';
}

export function daysUntil(date) {
    if (!date) return null;
    const diff = new Date(date) - new Date();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

export function timeUntil(date) {
    if (!date) return null;
    const diff = new Date(date) - new Date();
    if (diff <= 0) return 'Closed';

    const days    = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours   = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    if (days > 0) return `${days}d ${hours}h`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
}

export function urgencyLevel(date) {
    const days = daysUntil(date);
    if (days === null) return 'neutral';
    if (days <= 0)  return 'closed';
    if (days <= 3)  return 'urgent';
    if (days <= 7)  return 'warning';
    if (days <= 14) return 'caution';
    return 'neutral';
}
