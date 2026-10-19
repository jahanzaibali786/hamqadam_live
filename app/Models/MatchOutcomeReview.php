<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MatchOutcomeReview extends Model
{
    protected $fillable = [
        'member_a_id', 'member_b_id', 'status', 'verification_method',
        'happy_story_id', 'reviewed_by', 'reviewed_at', 'internal_notes',
    ];
    protected $casts = ['reviewed_at'=>'datetime'];
    public function memberA() { return $this->belongsTo(User::class,'member_a_id'); }
    public function memberB() { return $this->belongsTo(User::class,'member_b_id'); }
    public function story() { return $this->belongsTo(HappyStory::class,'happy_story_id'); }
    public function reviewer() { return $this->belongsTo(User::class,'reviewed_by'); }
}
