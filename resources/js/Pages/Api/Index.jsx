import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Copy, Plus, Trash2, Eye, EyeOff } from 'lucide-react';

function TokenRow({ token, onDelete }) {
    return (
        <div className="flex items-center justify-between py-3 border-b border-surface-100 last:border-0">
            <div>
                <p className="font-medium text-surface-800 text-sm">{token.name}</p>
                <p className="text-xs text-surface-400 mt-0.5">
                    {token.last_used_at
                        ? `Last used ${new Date(token.last_used_at).toLocaleDateString()}`
                        : 'Never used'
                    } · {token.calls_today} calls today
                </p>
            </div>
            <button
                onClick={() => onDelete(token.id)}
                className="text-surface-400 hover:text-red-500 transition-colors"
                title="Delete token"
            >
                <Trash2 size={15} />
            </button>
        </div>
    );
}

export default function ApiIndex({ tokens = [], callLimit = null }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });
    const [newToken, setNewToken] = useState(null);
    const [copied, setCopied] = useState(false);

    const handleCreate = (e) => {
        e.preventDefault();
        post('/api-access/tokens', {
            onSuccess: (page) => {
                const flash = page.props.flash ?? {};
                if (flash.new_token) setNewToken(flash.new_token);
                reset();
            },
        });
    };

    const handleDelete = (id) => {
        if (!confirm('Delete this token? It will stop working immediately.')) return;
        router.delete(`/api-access/tokens/${id}`);
    };

    const copyToken = () => {
        navigator.clipboard.writeText(newToken);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <AppLayout>
            <Head title="API Access — TenderIQ" />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h1 className="text-2xl font-bold text-surface-900 mb-2">API Access</h1>
                <p className="text-surface-500 mb-8 text-sm">
                    Use your API token to query TenderIQ tenders programmatically.
                    {callLimit
                        ? ` Your plan allows ${callLimit.toLocaleString()} API calls per day.`
                        : ' Your plan includes unlimited API calls.'
                    }
                </p>

                {/* New token reveal */}
                {newToken && (
                    <div className="mb-8 bg-green-50 border border-green-200 rounded-xl p-4">
                        <p className="text-sm font-semibold text-green-800 mb-2">
                            Token created — save it now, it won't be shown again.
                        </p>
                        <div className="flex items-center gap-2">
                            <code className="flex-1 bg-white border border-green-200 rounded-lg px-3 py-2 text-sm font-mono text-green-900 overflow-x-auto">
                                {newToken}
                            </code>
                            <button
                                onClick={copyToken}
                                className="btn-secondary flex items-center gap-1 text-xs shrink-0"
                            >
                                <Copy size={13} />
                                {copied ? 'Copied!' : 'Copy'}
                            </button>
                        </div>
                        <p className="text-xs text-green-600 mt-2">
                            Use as: <code>Authorization: Bearer {newToken.slice(0, 12)}...</code>
                        </p>
                    </div>
                )}

                {/* Existing tokens */}
                <div className="bg-white border border-surface-200 rounded-xl shadow-sm p-5 mb-6">
                    <h2 className="text-sm font-semibold text-surface-800 mb-4">Your API Tokens</h2>
                    {tokens.length === 0 ? (
                        <p className="text-sm text-surface-400">No tokens yet. Create one below.</p>
                    ) : (
                        tokens.map(t => <TokenRow key={t.id} token={t} onDelete={handleDelete} />)
                    )}
                </div>

                {/* Create new token */}
                {tokens.length < 5 && (
                    <form onSubmit={handleCreate} className="flex gap-3">
                        <input
                            type="text"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            placeholder="Token name (e.g. My App)"
                            className="flex-1 border border-surface-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-primary-400"
                            maxLength={100}
                        />
                        <button
                            type="submit"
                            disabled={processing || !data.name}
                            className="btn-primary flex items-center gap-1.5 text-sm shrink-0"
                        >
                            <Plus size={14} />
                            Create Token
                        </button>
                    </form>
                )}

                {/* Docs */}
                <div className="mt-10 bg-surface-50 rounded-xl p-6">
                    <h2 className="font-semibold text-surface-800 mb-3">Quick Start</h2>
                    <pre className="text-xs bg-surface-900 text-green-400 rounded-lg p-4 overflow-x-auto">
{`curl https://tenderiq.com/api/v1/tenders \\
  -H "Authorization: Bearer YOUR_TOKEN" \\
  -G -d "country=AE&per_page=20"`}
                    </pre>
                    <p className="text-xs text-surface-500 mt-3">
                        Endpoints: <code>GET /api/v1/tenders</code> · <code>GET /api/v1/tenders/{'{number}'}</code>
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
