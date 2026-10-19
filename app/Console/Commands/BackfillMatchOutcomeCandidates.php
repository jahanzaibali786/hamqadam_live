<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Models\MatchSuccess;
use App\Services\ClientScope\OutcomeVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
final class BackfillMatchOutcomeCandidates extends Command {
    protected $signature = 'hamqadam:match-outcome-candidates {--limit=500} {--dry-run}';
    protected $description = 'Discover mutually confirmed Got Match pairs for independent staff review, optionally insert candidate records';
    public function handle(OutcomeVerificationService $service): int {
        if(!Schema::hasTable('match_outcome_reviews')) { $this->error('Apply the match outcome migration first.'); return self::FAILURE; }
        $limit=max(1,min(5000,(int)$this->option('limit')));
        $seen=[];$pairs=0;$created=0;
        MatchSuccess::query()->where('status','got_match')->orderBy('id')->limit($limit)->get()->each(function($row)use($service,&$seen,&$pairs,&$created){
            $a=min((int)$row->user_id,(int)$row->matched_user_id);$b=max((int)$row->user_id,(int)$row->matched_user_id);
            if($a===$b||isset($seen[$a.':'.$b]))return;
            $seen[$a.':'.$b]=true;
            if(!MatchSuccess::query()->where('user_id',$b)->where('matched_user_id',$a)->where('status','got_match')->exists())return;
            $pairs++;
            if(!$this->option('dry-run') && $service->nominate($a,$b))$created++;
        });
        $this->info("Mutually reported pairs: {$pairs}; candidate upserts: {$created}; dry-run: ".($this->option('dry-run')?'yes':'no'));
        return self::SUCCESS;
    }
}
