import { router } from '@inertiajs/react'
import { CreditCard, ExternalLink, AlertCircle } from 'lucide-react'

export default function SubscriptionCard({ subscription, plan }) {
  const planColors = {
    free:         'bg-slate-100 text-slate-600',
    starter:      'bg-teal-100 text-teal-700',
    professional: 'bg-blue-100 text-blue-700',
    enterprise:   'bg-purple-100 text-purple-700',
  }

  const handlePortal   = () => router.visit('/subscription/portal')
  const handleUpgrade  = () => router.visit('/pricing')
  const handleCancel   = () => {
    if (confirm('Cancel subscription? You keep access until end of billing period.')) {
      router.post('/subscription/cancel')
    }
  }
  const handleResume   = () => router.post('/subscription/resume')

  return (
    <div className="bg-white border border-surface-200 rounded-2xl p-6 shadow-card">
      <div className="flex items-center gap-3 mb-4">
        <CreditCard size={20} className="text-surface-400" />
        <h3 className="font-semibold text-surface-900">Subscription</h3>
      </div>

      <div className="flex items-center justify-between mb-4">
        <span className="text-sm text-surface-500">Current Plan</span>
        <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${planColors[plan] ?? planColors.free}`}>
          {plan.charAt(0).toUpperCase() + plan.slice(1)}
        </span>
      </div>

      {subscription?.renews_at && !subscription?.on_grace_period && (
        <div className="flex items-center justify-between mb-4 text-sm">
          <span className="text-surface-500">Renews</span>
          <span className="text-surface-700">{subscription.renews_at}</span>
        </div>
      )}

      {subscription?.on_grace_period && (
        <div className="flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4 text-sm">
          <AlertCircle size={16} className="text-amber-500 mt-0.5 shrink-0" />
          <div>
            <p className="text-amber-800 font-medium">Subscription cancelled</p>
            <p className="text-amber-600 text-xs">Access until {subscription.ends_at}</p>
          </div>
        </div>
      )}

      <div className="flex flex-col gap-2 mt-2">
        {plan === 'free' ? (
          <button
            onClick={handleUpgrade}
            className="w-full bg-teal-500 hover:bg-teal-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors"
          >
            Upgrade Plan
          </button>
        ) : subscription?.on_grace_period ? (
          <button
            onClick={handleResume}
            className="w-full bg-teal-500 hover:bg-teal-600 text-white py-2 rounded-lg text-sm font-semibold transition-colors"
          >
            Resume Subscription
          </button>
        ) : (
          <>
            <button
              onClick={handlePortal}
              className="w-full flex items-center justify-center gap-2 bg-white border border-surface-200 hover:bg-surface-50 text-surface-700 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              <ExternalLink size={14} />
              Manage Billing
            </button>
            <button onClick={handleUpgrade} className="w-full text-sm text-surface-400 hover:text-surface-600 py-1">
              Change Plan
            </button>
            <button onClick={handleCancel} className="w-full text-xs text-red-400 hover:text-red-600 py-1">
              Cancel Subscription
            </button>
          </>
        )}
      </div>
    </div>
  )
}
