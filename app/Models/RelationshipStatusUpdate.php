<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipStatusUpdate extends Model
{
    protected $fillable = [
        'user_id',
        'partner_user_id',
        'express_interest_id',
        'status',
        'status_date',
        'notes',
        'is_public',
        'moderation_status',
    ];

    protected $casts = [
        'status_date' => 'date',
        'is_public' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'partner_user_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'express_interest_id');
    }
}
