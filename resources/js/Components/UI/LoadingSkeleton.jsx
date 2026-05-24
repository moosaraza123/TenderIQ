function SkeletonBox({ className = '' }) {
    return (
        <div
            className={`bg-gradient-to-r from-surface-100 via-surface-50 to-surface-100 bg-[length:200%_100%] animate-shimmer rounded ${className}`}
        />
    );
}

export function TenderCardSkeleton() {
    return (
        <div className="bg-white border border-surface-200 rounded-card shadow-card p-5">
            <div className="flex items-center gap-2 mb-3">
                <SkeletonBox className="h-5 w-16" />
                <SkeletonBox className="h-5 w-20" />
                <div className="ml-auto">
                    <SkeletonBox className="h-4 w-24" />
                </div>
            </div>
            <SkeletonBox className="h-5 w-3/4 mb-2" />
            <SkeletonBox className="h-4 w-1/2 mb-4" />
            <SkeletonBox className="h-4 w-full mb-1" />
            <SkeletonBox className="h-4 w-5/6 mb-4" />
            <div className="flex items-center justify-between">
                <SkeletonBox className="h-4 w-28" />
                <SkeletonBox className="h-8 w-24 rounded-button" />
            </div>
        </div>
    );
}

export default function LoadingSkeleton({ count = 5 }) {
    return (
        <div className="flex flex-col gap-3">
            {Array.from({ length: count }, (_, i) => (
                <TenderCardSkeleton key={i} />
            ))}
        </div>
    );
}
