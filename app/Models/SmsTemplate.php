<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'identifier',
        'sms_body',
        'template_id',
        'status',
    ];
}

