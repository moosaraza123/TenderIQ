import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/UI/Pagination';

export default function Users({ users }) {
    return (
        <AppLayout>
            <Head title="Admin — Users" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div className="flex items-center gap-4 mb-6">
                    <Link href="/admin" className="btn-ghost text-sm">← Back</Link>
                    <h1 className="text-xl font-bold text-surface-900">Users</h1>
                </div>

                <div className="bg-white border border-surface-200 rounded-card shadow-card overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-surface-50 border-b border-surface-200">
                            <tr>
                                {['Name', 'Email', 'Company', 'Plan', 'Joined'].map(h => (
                                    <th key={h} className="text-left text-xs font-semibold text-surface-400 uppercase tracking-wide px-4 py-3">{h}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {users.data?.map(user => (
                                <tr key={user.id} className="border-b border-surface-100 hover:bg-surface-50">
                                    <td className="px-4 py-3 font-medium text-surface-800">{user.name}</td>
                                    <td className="px-4 py-3 text-surface-400">{user.email}</td>
                                    <td className="px-4 py-3 text-surface-400">{user.company_name ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <span className={`text-xs font-semibold px-2 py-0.5 rounded-badge capitalize ${
                                            user.subscription_plan === 'pro'   ? 'bg-purple-100 text-purple-700' :
                                            user.subscription_plan === 'basic' ? 'bg-primary-100 text-primary-700' :
                                            'bg-surface-100 text-surface-600'
                                        }`}>
                                            {user.subscription_plan}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-surface-400 text-xs">
                                        {new Date(user.created_at).toLocaleDateString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination meta={users} baseUrl="/admin/users" />
            </div>
        </AppLayout>
    );
}
