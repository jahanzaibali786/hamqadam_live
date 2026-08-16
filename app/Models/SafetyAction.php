<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SafetyActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyAction extends Model
{
    protected $fillable = ['actor_user_id', 'target_user_id', 'action_type', 'reason', 'expires_at', 'metadata'];

    protected $casts = [
        'action_type' => SafetyActionType::class,
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
