<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class HoroscopeProfileMatch extends Model
{
    protected $fillable = ['user_id', 'match_id', 'match_count'];

    public function user(){
      return $this->belongsTo(User::class, 'match_id');
    }
}
