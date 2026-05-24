<?php

namespace App\Modules\Tender\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderView extends Model
{
    public $timestamps = false;

    protected $fillable = ['tender_id', 'user_id', 'ip_address', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
