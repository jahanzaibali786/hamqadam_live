<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyPrivateNote extends Model
{
    protected $fillable = [
        'profile_user_id',
        'author_user_id',
        'note',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
