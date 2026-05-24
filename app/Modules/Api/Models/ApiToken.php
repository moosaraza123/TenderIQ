<?php

namespace App\Modules\Api\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'calls_today'];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generate(User $user, string $name): self
    {
        return self::create([
            'user_id' => $user->id,
            'name'    => $name,
            'token'   => hash('sha256', Str::random(60)),
        ]);
    }
}
