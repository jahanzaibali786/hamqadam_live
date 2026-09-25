<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gift extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'thumbnail',
        'animated_asset',
        'coins',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'coins' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftTransaction::class);
    }
}
