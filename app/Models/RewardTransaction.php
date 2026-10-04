<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RewardTransaction extends Model { protected $guarded=[]; protected $casts=['metadata'=>'array']; public function rule(){return $this->belongsTo(RewardRule::class,'reward_rule_id');} }
