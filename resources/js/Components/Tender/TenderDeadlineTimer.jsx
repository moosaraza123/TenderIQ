import { Clock } from 'lucide-react';
import { useCountdown } from '@/hooks/useCountdown';

const LEVEL_CLASSES = {
    neutral: 'text-surface-400',
    caution: 'text-amber-600',
    warning: 'text-orange-500',
    urgent:  'text-red-600 font-bold animate-pulse-soft',
    closed:  'text-surface-300',
};

export default function TenderDeadlineTimer({ closingAt, showLabel = true }) {
    const { display, level } = useCountdown(closingAt);

    if (!display) return null;

    return (
        <span className={`inline-flex items-center gap-1 text-xs ${LEVEL_CLASSES[level] ?? LEVEL_CLASSES.neutral}`}>
            <Clock size={12} />
            {showLabel && level === 'urgent' ? 'Closes in ' : ''}
            {display}
        </span>
    );
}
