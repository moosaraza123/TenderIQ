import { Head, Link, useForm } from '@inertiajs/react';
import { FileText } from 'lucide-react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        email:    '',
        password: '',
        remember: false,
    });

    function submit(e) {
        e.preventDefault();
        post('/login');
    }

    return (
        <div className="min-h-screen bg-surface-50 flex items-center justify-center p-4">
            <Head title="Sign in" />

            <div className="w-full max-w-sm">
                {/* Logo */}
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <div className="w-10 h-10 bg-primary-500 rounded-xl flex items-center justify-center">
                            <FileText size={18} className="text-white" />
                        </div>
                        <span className="font-bold text-xl text-surface-900">TenderIQ</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-surface-900 mt-4">Welcome back</h1>
                    <p className="text-sm text-surface-400 mt-1">Sign in to your account</p>
                </div>

                <div className="bg-white border border-surface-200 rounded-card shadow-card p-6">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div>
                            <label className="text-sm font-medium text-surface-700 block mb-1.5">Email</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                className="w-full text-sm border border-surface-200 rounded-button px-3 py-2.5 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
                                placeholder="you@company.com"
                            />
                            {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="text-sm font-medium text-surface-700 block mb-1.5">Password</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                className="w-full text-sm border border-surface-200 rounded-button px-3 py-2.5 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
                                placeholder="••••••••"
                            />
                            {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                        </div>

                        <div className="flex items-center">
                            <label className="flex items-center gap-2 text-sm text-surface-600 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="accent-primary-500"
                                />
                                Remember me
                            </label>
                        </div>

                        <button type="submit" disabled={processing} className="btn-primary w-full py-2.5">
                            {processing ? 'Signing in…' : 'Sign in'}
                        </button>
                    </form>
                </div>

                <p className="text-center text-sm text-surface-400 mt-4">
                    Don't have an account?{' '}
                    <Link href="/register" className="text-primary-600 font-medium hover:underline">Sign up free</Link>
                </p>
            </div>
        </div>
    );
}
