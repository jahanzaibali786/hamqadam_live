<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = [
        'type', 'title', 'opening', 'body', 'closing', 'language',
        'status', 'display_order', 'created_by', 'updated_by',
    ];
}
