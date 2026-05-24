import { router } from '@inertiajs/react';

const PLAN_LABELS = {
    basic:      { name: 'Basic', price: '$49/mo', countries: 'UAE' },
    pro:        { name: 'Pro',   price: '$99/mo', countries: 'UAE + Saudi Arabia' },
    enterprise: { name: 'Enterprise', price: '$199/mo', countries: 'Worldwide' },
};

export default function UpgradeModal({ isOpen, onClose, requiredPlan = 'basic' }) {
    if (!isOpen) return null;

    const plan = PLAN_LABELS[requiredPlan] || PLAN_LABELS.basic;

    const handleUpgrade = () => {
        onClose();
        router.visit('/pricing');
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-black/50" onClick={onClose} />
            <div className="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-8 text-center">
                <div className="text-4xl mb-3">🔒</div>
                <h2 className="text-xl font-bold text-surface-900 mb-2">
                    {plan.countries} Tenders Require {plan.name}
                </h2>
                <p className="text-surface-600 mb-6 text-sm">
                    Unlock access to {plan.countries} government tenders starting at {plan.price}.
                    Cancel anytime.
                </p>

                <div className="flex flex-col gap-3">
                    <button
                        onClick={handleUpgrade}
                        className="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-3 rounded-lg transition-colors"
                    >
                        View Pricing Plans
                    </button>
                    <button
                        onClick={onClose}
                        className="w-full text-surface-500 hover:text-surface-700 text-sm py-2"
                    >
                        Maybe later
                    </button>
                </div>
            </div>
        </div>
    );
}
