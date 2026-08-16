<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class ProfileMatch extends Model
{
    protected $fillable = [
        'user_id',
        'match_id',
        'match_percentage',
        'score_breakdown',
        'compatibility_reasons',
        'compatibility_explanation',
        'calculated_at',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'compatibility_reasons' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function user(){
      return $this->belongsTo(User::class, 'match_id');
    }

    public function owner()
    {
      return $this->belongsTo(User::class, 'user_id');
    }

    public function matchedUser()
    {
      return $this->belongsTo(User::class, 'match_id');
    }
}
