<?php

namespace App\Modules\User\Models;

use App\Modules\Alert\Models\AlertSubscription;
use App\Modules\Api\Models\ApiToken;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name', 'email', 'password',
        'company_name', 'phone', 'country',
        'subscription_plan', 'subscription_expires_at',
        'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at',
        'api_calls_today', 'is_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'subscription_expires_at' => 'datetime',
            'is_admin'                => 'boolean',
        ];
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(AlertSubscription::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function hasActivePlan(array $plans): bool
    {
        if (! in_array($this->subscription_plan, $plans)) {
            return false;
        }

        return $this->subscription_expires_at === null
            || $this->subscription_expires_at->isFuture();
    }

    public function isSubscribed(): bool
    {
        return $this->hasActivePlan(['starter', 'professional', 'enterprise']);
    }

    public function isPro(): bool
    {
        return $this->hasActivePlan(['professional', 'enterprise']);
    }

    public function isEnterprise(): bool
    {
        return $this->hasActivePlan(['enterprise']);
    }

    public function canViewSummary(): bool
    {
        return $this->isSubscribed();
    }

    public function canDownloadPdf(): bool
    {
        return $this->isSubscribed();
    }

    public function canViewRecommendation(): bool
    {
        return $this->isPro();
    }

    public function canExportCsv(): bool
    {
        return $this->isPro();
    }

    public function alertLimit(): int
    {
        return match ($this->subscription_plan) {
            'enterprise'   => 9999,
            'professional' => 20,
            'starter'      => 5,
            default        => 1,
        };
    }

    public function apiCallLimit(): int
    {
        return match ($this->subscription_plan) {
            'enterprise' => 1000,
            default      => 0,
        };
    }

    public function dailyViewLimit(): int
    {
        return $this->isSubscribed() ? PHP_INT_MAX : 5;
    }
}
