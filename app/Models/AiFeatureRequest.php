<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiFeatureRequest extends Model
{
    protected $fillable = ['user_id', 'feature', 'prompt', 'input', 'output', 'provider', 'status', 'tokens_used'];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];
}
