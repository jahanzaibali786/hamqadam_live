<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalEvent extends Model
{
    protected $fillable = [
        'express_interest_id',
        'actor_id',
        'event',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'express_interest_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
