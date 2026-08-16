<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpressInterest extends Model
{
    protected $fillable = [
        'user_id',
        'interested_by',
        'status',
        'initial_note',
        'responded_at',
        'withdrawn_at',
        'cancelled_at',
        'expires_at',
        'expired_at',
        'compatibility_snapshot',
    ];

    protected $casts = [
        'status' => ProposalStatus::class,
        'responded_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interested_by')->withTrashed();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProposalNote::class, 'express_interest_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProposalEvent::class, 'express_interest_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ProposalMeeting::class, 'express_interest_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasmany(Notification::class);
    }
}
