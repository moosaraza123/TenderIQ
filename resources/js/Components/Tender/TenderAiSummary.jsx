import { Sparkles } from 'lucide-react';
import LockedOverlay from '@/Components/UI/LockedOverlay';
import { truncate } from '@/lib/formatters';

export default function TenderAiSummary({ summary, canView, snippet = false }) {
    return (
        <LockedOverlay
            isLocked={!canView}
            message="Upgrade to see AI-generated summaries"
            plan="basic"
        >
            <div className="flex items-start gap-2">
                <Sparkles size={14} className="text-primary-500 mt-0.5 shrink-0" />
                <p className="text-sm text-surface-700 leading-relaxed">
                    {snippet ? truncate(summary, 100) : summary}
                </p>
            </div>
        </LockedOverlay>
    );
}
