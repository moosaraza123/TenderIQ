import Badge from '@/Components/UI/Badge';
import { STATUS_VARIANTS, CATEGORY_VARIANTS, RECOMMENDATION_VARIANTS } from '@/lib/constants';

export function StatusBadge({ status }) {
    const variant = STATUS_VARIANTS[status] ?? 'default';
    return <Badge variant={variant}>{status}</Badge>;
}

export function CategoryBadge({ category }) {
    const variant = CATEGORY_VARIANTS[category] ?? 'default';
    const short   = category?.replace(' Services', '') ?? category;
    return <Badge variant={variant}>{short}</Badge>;
}

export function RecommendationBadge({ recommendation }) {
    const variant = RECOMMENDATION_VARIANTS[recommendation] ?? 'default';
    return <Badge variant={variant}>{recommendation}</Badge>;
}
