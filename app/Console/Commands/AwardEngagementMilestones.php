<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ClientScope\EngagementMilestoneService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AwardEngagementMilestones extends Command
{
    protected $signature = 'rewards:award-milestones {--limit=250}';
    protected $description = 'Award server-verified, configured one-time engagement milestones.';

    public function handle(EngagementMilestoneService $service): int
    {
        if (! Schema::hasTable('reward_rules') || ! Schema::hasTable('reward_transactions')) return self::SUCCESS;
        $limit = min(2000, max(1, (int) $this->option('limit')));
        $awards = 0;
        $cursor = (int) \Illuminate\Support\Facades\Cache::get('hq_milestone_last_user_id', 0);
        $query = User::query()->where('user_type', 'member')->where('approved', 1)
            ->whereNotNull('email_verified_at')->with('member')->where('id','>',$cursor);
        if (! (clone $query)->exists()) {
            $cursor=0;
            $query = User::query()->where('user_type', 'member')->where('approved', 1)
                ->whereNotNull('email_verified_at')->with('member');
        }
        $candidates = $query->orderBy('id')->limit($limit)->get();
        $candidates->each(function (User $user) use ($service, &$awards) {
                if ($user->member && $service->issue($user, 'profile_completed', (int) $user->member->id)) $awards++;
                $first = DB::table('express_interests')->where('interested_by', $user->id)->orderBy('id')->value('id');
                if ($first && $service->issue($user, 'first_interest', (int) $first)) $awards++;
            });
        if ($candidates->isNotEmpty()) {
            \Illuminate\Support\Facades\Cache::put('hq_milestone_last_user_id', $candidates->last()->id, now()->addDays(30));
        }
        $this->info('Milestone credits recorded: '.$awards);
        return self::SUCCESS;
    }
}
