import { useForm } from '@inertiajs/react';
import { Bell, BellOff, Trash2 } from 'lucide-react';

export default function AlertCard({ alert }) {
    const { patch, delete: destroy, processing } = useForm();

    function toggle() {
        patch(`/alerts/${alert.id}/toggle`);
    }

    function remove() {
        if (!confirm('Delete this alert?')) return;
        destroy(`/alerts/${alert.id}`);
    }

    return (
        <div className="bg-white border border-surface-200 rounded-card shadow-card p-4 flex items-start justify-between gap-4">
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                    {alert.is_active
                        ? <Bell size={14} className="text-primary-500" />
                        : <BellOff size={14} className="text-surface-300" />
                    }
                    <span className="text-sm font-semibold text-surface-800">
                        {alert.keywords?.join(', ')}
                    </span>
                </div>
                <div className="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-surface-400 mt-1">
                    <span className="capitalize">{alert.frequency ?? 'daily'}</span>
                    {alert.categories?.length > 0 && (
                        <span>· {alert.categories.join(', ')}</span>
                    )}
                    {alert.cities?.length > 0 && (
                        <span>· {alert.cities.join(', ')}</span>
                    )}
                    {(alert.min_budget || alert.max_budget) && (
                        <span>· PKR {alert.min_budget ?? '0'} – {alert.max_budget ?? '∞'}</span>
                    )}
                    {alert.match_count > 0 && (
                        <span>· {alert.match_count} matches</span>
                    )}
                    {alert.last_triggered_at && (
                        <span>· Last triggered: {new Date(alert.last_triggered_at).toLocaleDateString()}</span>
                    )}
                </div>
            </div>

            <div className="flex items-center gap-2 shrink-0">
                <button
                    onClick={toggle}
                    disabled={processing}
                    className={`text-xs px-3 py-1.5 rounded-button border transition-colors ${
                        alert.is_active
                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                            : 'bg-surface-50 text-surface-500 border-surface-200 hover:bg-surface-100'
                    }`}
                >
                    {alert.is_active ? 'Active' : 'Paused'}
                </button>
                <button onClick={remove} disabled={processing} className="p-1.5 text-surface-400 hover:text-red-500 transition-colors">
                    <Trash2 size={14} />
                </button>
            </div>
        </div>
    );
}
