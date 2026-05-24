<?php

namespace App\Modules\Tender\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Tender extends Model
{
    use Searchable;

    protected $fillable = [
        'tender_number', 'title', 'title_arabic',
        'description', 'description_arabic',
        'organization_name', 'organization_name_arabic',
        'ministry', 'category', 'sector', 'city', 'region',
        'country', 'country_code', 'currency', 'budget',
        'tender_type', 'status', 'tier', 'source', 'source_id',
        'advertised_at', 'closing_at',
        'source_url', 'detail_url', 'pdf_urls',
        'ai_summary', 'ai_summary_arabic', 'ai_eligibility',
        'ai_budget_extracted', 'ai_recommendation',
        'ai_key_requirements', 'is_summarized',
        'is_featured', 'quality_score', 'view_count',
        'duplicate_of', 'scraped_at',
    ];

    protected $casts = [
        'pdf_urls'            => 'array',
        'ai_key_requirements' => 'array',
        'budget'              => 'decimal:2',
        'ai_budget_extracted' => 'decimal:2',
        'is_summarized'       => 'boolean',
        'is_featured'         => 'boolean',
        'advertised_at'       => 'date',
        'closing_at'          => 'datetime',
        'scraped_at'          => 'datetime',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id'                => $this->id,
            'tender_number'     => $this->tender_number,
            'title'             => $this->title,
            'description'       => $this->description,
            'organization_name' => $this->organization_name,
            'ministry'          => $this->ministry,
            'category'          => $this->category,
            'sector'            => $this->sector,
            'city'              => $this->city,
            'country_code'      => $this->country_code,
            'source'            => $this->source,
            'tier'              => $this->tier,
            'status'            => $this->status,
            'closing_at'        => $this->closing_at?->timestamp,
        ];
    }

    public function views(): HasMany
    {
        return $this->hasMany(TenderView::class);
    }

    public function isFree(): bool
    {
        return $this->tier === 'free';
    }

    public function isPaid(): bool
    {
        return $this->tier === 'paid';
    }

    public function isPremium(): bool
    {
        return in_array($this->tier, ['premium', 'enterprise']);
    }

    public function scopePublished($query)
    {
        return $query->whereIn('status', ['Published', 'Corrigendum']);
    }

    public function scopeActive($query)
    {
        return $query->published()->where('closing_at', '>', now());
    }

    public function scopeClosingSoon($query, int $days = 7)
    {
        return $query->active()->where('closing_at', '<=', now()->addDays($days));
    }

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', strtoupper($countryCode));
    }

    public function scopeForTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function getIsClosingSoonAttribute(): bool
    {
        return $this->closing_at && $this->closing_at->diffInDays(now()) <= 7 && $this->closing_at->gt(now());
    }
}
