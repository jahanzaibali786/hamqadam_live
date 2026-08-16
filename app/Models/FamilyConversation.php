<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyConversation extends Model
{
    protected $fillable = [
        'proposal_id',
        'first_profile_user_id',
        'second_profile_user_id',
        'created_by',
        'status',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ExpressInterest::class, 'proposal_id');
    }

    public function firstProfile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_profile_user_id');
    }

    public function secondProfile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_profile_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FamilyConversationMessage::class);
    }
}
