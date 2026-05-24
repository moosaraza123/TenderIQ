import { Lock } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function LockedOverlay({
    isLocked,
    message = 'Upgrade to unlock this feature',
    plan = 'basic',
    children,
}) {
    if (!isLocked) return <>{children}</>;

    const planLabel = plan === 'pro' ? 'Pro' : 'Basic';
    const price     = plan === 'pro' ? '$49/mo' : '$19/mo';

    return (
        <div className="relative">
            <div className="filter blur-sm pointer-events-none select-none" aria-hidden="true">
                {children}
            </div>
            <div className="absolute inset-0 bg-gradient-to-b from-transparent to-white/90 flex items-end justify-center pb-4">
                <div className="bg-white border border-surface-200 rounded-card shadow-card p-5 text-center max-w-xs">
                    <Lock size={20} className="text-surface-300 mx-auto mb-2" />
                    <p className="text-sm font-semibold text-surface-800 mb-1">{message}</p>
                    <p className="text-xs text-surface-400 mb-3">{planLabel} plan · {price}</p>
                    <Link href="/register" className="btn-primary inline-block">
                        Upgrade now
                    </Link>
                </div>
            </div>
        </div>
    );
}
