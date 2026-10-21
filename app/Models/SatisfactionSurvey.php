<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SatisfactionSurvey extends Model
{
    protected $fillable = ['user_id', 'score', 'comment', 'context', 'platform'];
    protected $casts = ['score' => 'integer'];
}
