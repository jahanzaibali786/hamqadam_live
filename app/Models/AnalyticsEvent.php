<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AnalyticsEvent extends Model { protected $guarded=[]; protected $casts=['metadata'=>'array','occurred_at'=>'datetime']; }
