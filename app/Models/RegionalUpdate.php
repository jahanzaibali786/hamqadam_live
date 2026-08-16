<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionalUpdate extends Model
{
    protected $fillable = ['region', 'title', 'body', 'is_active', 'publish_at'];

    protected $casts = ['is_active' => 'boolean', 'publish_at' => 'datetime'];
}
