<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RewardRule extends Model { protected $guarded=[]; protected $casts=['active'=>'boolean','conditions'=>'array']; }
