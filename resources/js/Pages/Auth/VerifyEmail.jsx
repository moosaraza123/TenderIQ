import { Head, useForm } from '@inertiajs/react';

export default function VerifyEmail() {
    const { post, processing } = useForm();

    return (
        <div className="min-h-screen bg-surface-50 flex items-center justify-center p-4">
            <Head title="Verify Email" />
            <div className="max-w-md text-center">
                <div className="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <span className="text-2xl">✉️</span>
                </div>
                <h1 className="text-xl font-bold text-surface-900 mb-2">Check your email</h1>
                <p className="text-sm text-surface-400 mb-6">
                    We sent a verification link to your email address. Click the link to verify your account.
                </p>
                <button
                    onClick={() => post('/email/verification-notification')}
                    disabled={processing}
                    className="btn-secondary"
                >
                    {processing ? 'Sending…' : 'Resend verification email'}
                </button>
            </div>
        </div>
    );
}
