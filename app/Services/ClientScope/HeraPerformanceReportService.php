<?php
declare(strict_types=1);
namespace App\Services\ClientScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
final class HeraPerformanceReportService {
    public function report(): array {
        if(!Schema::hasTable('profile_matches')||!Schema::hasColumn('profile_matches','ai_enhanced')) return [];
        $records=DB::table('profile_matches')
            ->selectRaw('COUNT(*) as recommendations, SUM(CASE WHEN ai_enhanced = 1 THEN 1 ELSE 0 END) as ai_assisted')
            ->selectRaw('AVG(CASE WHEN ai_enhanced = 1 THEN match_percentage ELSE NULL END) as mean_ai_match_score')
            ->first();
        $reviewed=['reviewed_pairs'=>0,'verified_pairs'=>0];
        if(Schema::hasTable('match_outcome_reviews')){
            $result=DB::table('match_outcome_reviews')->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as verified', ['verified'])->first();
            $reviewed=['reviewed_pairs'=>(int)($result->total??0),'verified_pairs'=>(int)($result->verified??0)];
        }
        return ['recommendations'=>(int)($records->recommendations??0),'ai_assisted'=>(int)($records->ai_assisted??0),
            'mean_ai_match_score'=> $records->mean_ai_match_score!==null?round((float)$records->mean_ai_match_score,2):null]
            +$reviewed+['limitation'=>'Recorded AI scores are not ground-truth accuracy. True evaluation requires matched recommendation-to-outcome history and an approved success definition.'];
    }
}
