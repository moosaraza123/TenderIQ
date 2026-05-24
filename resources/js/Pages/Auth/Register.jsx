import { Head, Link, useForm } from '@inertiajs/react';
import { FileText } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name:                  '',
        email:                 '',
        company_name:          '',
        phone:                 '',
        password:              '',
        password_confirmation: '',
    });

    function submit(e) {
        e.preventDefault();
        post('/register');
    }

    return (
        <div className="min-h-screen bg-surface-50 flex items-center justify-center p-4">
            <Head title="Create account" />

            <div className="w-full max-w-sm">
                <div className="text-center mb-8">
                    <Link href="/" className="inline-flex items-center gap-2">
                        <div className="w-10 h-10 bg-primary-500 rounded-xl flex items-center justify-center">
                            <FileText size={18} className="text-white" />
                        </div>
                        <span className="font-bold text-xl text-surface-900">TenderIQ</span>
                    </Link>
                    <h1 className="text-xl font-semibold text-surface-900 mt-4">Create free account</h1>
                    <p className="text-sm text-surface-400 mt-1">Start finding government tenders today</p>
                </div>

                <div className="bg-white border border-surface-200 rounded-card shadow-card p-6">
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        {[
                            { key: 'name',         label: 'Full Name',        type: 'text',     placeholder: 'Ahmed Khan',       required: true },
                            { key: 'email',        label: 'Email',            type: 'email',    placeholder: 'ahmed@company.pk', required: true },
                            { key: 'company_name', label: 'Company Name',     type: 'text',     placeholder: 'Khan Enterprises',  required: false },
                            { key: 'phone',        label: 'Phone',            type: 'tel',      placeholder: '+92 300 1234567',   required: false },
                        ].map(({ key, label, type, placeholder, required }) => (
                            <div key={key}>
                                <label className="text-sm font-medium text-surface-700 block mb-1.5">
                                    {label}{required && <span className="text-red-500 ml-0.5">*</span>}
                                </label>
                                <input
                                    type={type}
                                    value={data[key]}
                                    onChange={(e) => setData(key, e.target.value)}
                                    placeholder={placeholder}
                                    className="w-full text-sm border border-surface-200 rounded-button px-3 py-2.5 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
                                />
                                {errors[key] && <p className="text-red-500 text-xs mt-1">{errors[key]}</p>}
                            </div>
                        ))}

                        <div>
                            <label className="text-sm font-medium text-surface-700 block mb-1.5">Password <span className="text-red-500">*</span></label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="new-password"
                                placeholder="Minimum 8 characters"
                                className="w-full text-sm border border-surface-200 rounded-button px-3 py-2.5 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
                            />
                            {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                        </div>

                        <div>
                            <label className="text-sm font-medium text-surface-700 block mb-1.5">Confirm Password <span className="text-red-500">*</span></label>
                            <input
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                autoComplete="new-password"
                                placeholder="Repeat password"
                                className="w-full text-sm border border-surface-200 rounded-button px-3 py-2.5 focus:outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-500/10"
                            />
                        </div>

                        <button type="submit" disabled={processing} className="btn-primary w-full py-2.5">
                            {processing ? 'Creating account…' : 'Create account'}
                        </button>
                    </form>
                </div>

                <p className="text-center text-sm text-surface-400 mt-4">
                    Already have an account?{' '}
                    <Link href="/login" className="text-primary-600 font-medium hover:underline">Sign in</Link>
                </p>
            </div>
        </div>
    );
}
