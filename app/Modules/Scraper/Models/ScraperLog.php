<?php

namespace App\Modules\Scraper\Models;

use Illuminate\Database\Eloquent\Model;

class ScraperLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_slug', 'started_at', 'completed_at',
        'total_found', 'new_inserted', 'updated', 'failed', 'error_log',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'error_log'    => 'array',
        'created_at'   => 'datetime',
    ];
}
