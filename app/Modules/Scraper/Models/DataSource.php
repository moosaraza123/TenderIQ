<?php

namespace App\Modules\Scraper\Models;

use Illuminate\Database\Eloquent\Model;

class DataSource extends Model
{
    protected $fillable = [
        'name', 'slug', 'country_code', 'tier', 'url',
        'scraper_class', 'scraper_type', 'is_active', 'requires_proxy',
        'scrape_frequency_hours', 'rate_limit_delay_seconds',
        'last_scraped_at', 'last_success_at',
        'total_tenders_scraped', 'success_rate_7d', 'notes',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'requires_proxy'   => 'boolean',
        'last_scraped_at'  => 'datetime',
        'last_success_at'  => 'datetime',
        'success_rate_7d'  => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
