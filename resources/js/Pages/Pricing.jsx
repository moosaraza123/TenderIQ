import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import PricingCard from '@/Components/Payment/PricingCard';

export default function Pricing({ userPlan = 'free', isAuthenticated = false }) {
    return (
        <AppLayout>
            <Head title="Pricing — TenderIQ" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div className="text-center mb-12">
                    <h1 className="text-4xl font-extrabold text-surface-900 mb-4">
                        Simple, transparent pricing
                    </h1>
                    <p className="text-lg text-surface-500 max-w-2xl mx-auto">
                        Start free with Pakistan tenders. Unlock UAE, Saudi Arabia, and worldwide
                        procurement notices as your business grows.
                    </p>
                </div>

                <PricingCard userPlan={userPlan} isAuthenticated={isAuthenticated} />

                <div className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <div>
                        <div className="text-3xl mb-2">🔒</div>
                        <h3 className="font-semibold text-surface-900 mb-1">Cancel anytime</h3>
                        <p className="text-sm text-surface-500">No long-term contracts. Downgrade or cancel from your dashboard.</p>
                    </div>
                    <div>
                        <div className="text-3xl mb-2">💳</div>
                        <h3 className="font-semibold text-surface-900 mb-1">Secure payments</h3>
                        <p className="text-sm text-surface-500">Powered by Stripe. Your payment details are never stored on our servers.</p>
                    </div>
                    <div>
                        <div className="text-3xl mb-2">📧</div>
                        <h3 className="font-semibold text-surface-900 mb-1">Priority support</h3>
                        <p className="text-sm text-surface-500">Pro and Enterprise subscribers get dedicated email support within 24 hours.</p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
