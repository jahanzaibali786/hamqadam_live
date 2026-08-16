<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarriageTip extends Model
{
    protected $fillable = ['title', 'body', 'category', 'is_active', 'publish_at'];

    protected $casts = ['is_active' => 'boolean', 'publish_at' => 'datetime'];
}
