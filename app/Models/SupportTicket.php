<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class);
    }
}

