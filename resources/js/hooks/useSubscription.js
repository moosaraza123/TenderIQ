import { usePage } from '@inertiajs/react';
import { PLAN_LIMITS } from '@/lib/constants';

export function useSubscription() {
    const { auth } = usePage().props;
    const user     = auth?.user;
    const plan     = user?.subscription_plan ?? 'guest';
    const limits   = PLAN_LIMITS[plan] ?? PLAN_LIMITS.free;

    const isSubscribed = plan === 'basic' || plan === 'pro';

    return {
        user,
        plan,
        limits,
        isLoggedIn:          !!user,
        isSubscribed,
        canViewSummary:      isSubscribed,
        canDownloadPdf:      isSubscribed,
        canViewRecommendation: plan === 'pro',
        canExportCsv:        plan === 'pro',
        alertLimit:          limits.alerts,
    };
}
