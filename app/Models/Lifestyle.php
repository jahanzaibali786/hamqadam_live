<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lifestyle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        // Registration step 9 writes `diet` here. It was absent from fillable,
        // so every write was silently discarded.
        'diet',
        'dietary_habits',
        'smoking_habits',
        'drinking_habits',
    ];
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
