<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * Rows scored before this moment came from the old rule-based formula that
     * counted "no preference" as a match (everyone ~85-100%). They are treated
     * as stale so the live scorer recomputes them. Override with
     * MATCH_SCORES_VALID_AFTER in .env.
     */
    public function isCurrent(): bool
    {
        $cutoff = config('services.matching.scores_valid_after', '2026-10-27 12:00:00');
        $at = $this->calculated_at ?? $this->updated_at;

        return $at !== null && $at->gte(\Carbon\Carbon::parse($cutoff));
    }

    /** A real pair is never a perfect 100: cap what members are shown. */
    public static function displayPercentage(?int $value): ?int
    {
        return $value === null ? null : min(98, max(0, $value));
    }

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

    /**
     * Match visibility shared by dashboard cards/counts and the full listing.
     * Always scope to the signed-in member, excluding hidden or unavailable profiles.
     */
    public function scopeVisibleToMember(Builder $query, User $member): Builder
    {
        return $query->where('user_id', $member->id)
            ->where('match_id', '!=', $member->id)
            ->where('match_percentage', '>', 0)
            ->whereHas('matchedUser', fn ($q) => $q
                ->where('user_type', 'member')
                ->where('approved', 1)
                ->where('blocked', 0)
                ->where('deactivated', 0)
                ->whereHas('member', fn ($m) => $m->where(fn ($x) => $x
                    ->where('hide_profile', 0)->orWhereNull('hide_profile'))))
            ->whereNotIn('match_id', function ($q) use ($member) {
                $q->select('user_id')->from('ignored_users')->where('ignored_by', $member->id);
            })
            ->whereNotIn('match_id', function ($q) use ($member) {
                $q->select('ignored_by')->from('ignored_users')->where('user_id', $member->id);
            });
    }

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
