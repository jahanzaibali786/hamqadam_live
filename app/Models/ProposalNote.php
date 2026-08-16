<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalNote extends Model
{
    protected $fillable = [
        'express_interest_id',
        'user_id',
        'note',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'express_interest_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
