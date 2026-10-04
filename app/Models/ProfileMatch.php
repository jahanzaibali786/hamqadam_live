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
        'score_breakdown_their',
        'mutual_matched_preferences',
        'one_sided_preferences',
        'score_balance',
        'compatibility_concerns',
        'recommended_actions',
        'confidence_score',
        'model_confidence',
        'ai_enhanced',
        'match_status',
        'compatibility_level',
        'calculated_at',
    ];

    protected $casts = [
        'score_breakdown' => 'array',
        'compatibility_reasons' => 'array',
        'score_breakdown_their' => 'array',
        'mutual_matched_preferences' => 'array',
        'one_sided_preferences' => 'array',
        'score_balance' => 'array',
        'compatibility_concerns' => 'array',
        'recommended_actions' => 'array',
        'confidence_score' => 'integer',
        'ai_enhanced' => 'boolean',
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
