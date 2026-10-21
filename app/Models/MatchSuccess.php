<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MatchSuccess extends Model
{
    protected $fillable = ['user_id','matched_user_id','profile_match_id','status','note','confirmed_at'];
    protected $casts = ['confirmed_at' => 'datetime'];
}
