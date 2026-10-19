<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use App\Models\MatchOutcomeReview;
use App\Models\MatchSuccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class OutcomeVerificationService {
    /** Create an internal review candidate only for mutually reported pairs. */
    public function nominate(int $userId,int $otherId): ?MatchOutcomeReview {
        if($userId===$otherId || ! Schema::hasTable('match_outcome_reviews')) return null;
        [$a,$b]=[min($userId,$otherId),max($userId,$otherId)];
        if(!MatchSuccess::where('user_id',$a)->where('matched_user_id',$b)->where('status','got_match')->exists()
            || !MatchSuccess::where('user_id',$b)->where('matched_user_id',$a)->where('status','got_match')->exists()) return null;
        return DB::transaction(fn()=>MatchOutcomeReview::firstOrCreate(
            ['member_a_id'=>$a,'member_b_id'=>$b],['status'=>'pending']
        ),3);
    }
}
