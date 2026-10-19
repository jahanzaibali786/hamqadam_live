<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HamqadamQaConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        foreach (['reward_rules','sponsored_listings','client_scope_settings'] as $table) {
            if (! Schema::hasTable($table)) throw new \RuntimeException("Run staging migrations first: {$table} is missing.");
        }
        foreach ([['profile_completed',15],['first_interest',5],['got_match',30]] as [$event,$coins]) {
            DB::table('reward_rules')->updateOrInsert(
                ['event_key'=>$event],
                ['title'=>"QA DEMO — {$event}",'coin_reward'=>$coins,'max_per_user'=>1,
                 'active'=>1,'conditions'=>json_encode(['qa_demo'=>true]),'updated_at'=>now(),'created_at'=>now()]
            );
        }
        DB::table('client_scope_settings')->updateOrInsert(
            ['key'=>'show_ads_to_paid'],['value'=>'0','updated_at'=>now(),'created_at'=>now()]
        );
        foreach (['home','discovery','match'] as $placement) {
            DB::table('sponsored_listings')->updateOrInsert(
                ['title'=>"QA DEMO — {$placement}"],
                ['sponsor_name'=>'QA DEMO — Not real advertising','placement'=>$placement,'priority'=>10,
                 'target_url'=>'https://example.com/','active'=>1,'starts_at'=>now()->subDay(),
                 'ends_at'=>now()->addDays(14),'impressions'=>0,'clicks'=>0,
                 'updated_at'=>now(),'created_at'=>now()]
            );
        }
    }
}
