<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    protected $fillable = ['user_id', 'filters', 'result_count'];

    protected $casts = [
        'filters' => 'array',
    ];
}
