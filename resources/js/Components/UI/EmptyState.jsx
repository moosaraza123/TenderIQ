import { Search } from 'lucide-react';

export default function EmptyState({
    icon: Icon = Search,
    heading = 'No tenders found',
    sub = 'Try adjusting your filters or search term',
    action,
    actionLabel = 'Clear filters',
}) {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <Icon size={48} className="text-surface-300 mb-4" strokeWidth={1.5} />
            <h3 className="text-base font-semibold text-surface-800 mb-1">{heading}</h3>
            <p className="text-sm text-surface-400 mb-6">{sub}</p>
            {action && (
                <button onClick={action} className="btn-ghost">
                    {actionLabel}
                </button>
            )}
        </div>
    );
}
