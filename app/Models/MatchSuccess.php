<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MatchSuccess extends Model { protected $guarded=[]; protected $casts=['confirmed_at'=>'datetime']; public function matchedUser(){return $this->belongsTo(User::class,'matched_user_id');} }
