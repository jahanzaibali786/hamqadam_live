<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhysicalAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'height',
        'weight',
        'body_type',
        'complexion',
        'blood_group',
        'disability',
    ];
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
