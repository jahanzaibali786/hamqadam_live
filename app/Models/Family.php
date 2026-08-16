<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'father',
        'father_occupation',
        'mother',
        'mother_occupation',
        'sibling',
        'no_of_sisters',
        'no_of_brothers',
        'about_parents',
        'about_siblings',
        'about_relatives',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
