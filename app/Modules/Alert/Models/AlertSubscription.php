<?php

namespace App\Modules\Alert\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertSubscription extends Model
{
    protected $fillable = [
        'user_id', 'name', 'keywords', 'categories', 'cities',
        'countries', 'sources', 'webhook_url',
        'min_budget', 'max_budget', 'is_active',
        'frequency', 'last_triggered_at', 'match_count',
    ];

    protected $casts = [
        'keywords'          => 'array',
        'categories'        => 'array',
        'cities'            => 'array',
        'countries'         => 'array',
        'sources'           => 'array',
        'min_budget'        => 'decimal:2',
        'max_budget'        => 'decimal:2',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
