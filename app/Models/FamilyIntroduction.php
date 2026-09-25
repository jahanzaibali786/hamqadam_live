<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyIntroduction extends Model
{
    protected $fillable = [
        'proposal_id',
        'initiated_by',
        'status',
        'first_guardian_link_id',
        'second_guardian_link_id',
        'accepted_at',
        'cancelled_at',
        'message',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'proposal_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function firstGuardianLink(): BelongsTo
    {
        return $this->belongsTo(FamilyGuardianLink::class, 'first_guardian_link_id');
    }

    public function secondGuardianLink(): BelongsTo
    {
        return $this->belongsTo(FamilyGuardianLink::class, 'second_guardian_link_id');
    }

    public function conversation(): HasMany
    {
        return $this->hasMany(FamilyConversation::class, 'proposal_id', 'proposal_id');
    }
}
