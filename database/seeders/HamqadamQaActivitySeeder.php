<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class HamqadamQaActivitySeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        if (! Schema::hasTable('analytics_events')) return;
        $user = DB::table('users')->where('email','demo.user@hamqadam.test')->value('id');
        if (! $user) {
            $this->command?->warn('No QA member exists; analytics demo event skipped.');
            return;
        }
        DB::table('analytics_events')->updateOrInsert(
            ['session_key'=>'qa-demo-activity-'.$user,'source'=>'qa_demo_fixture'],
            ['user_id'=>$user,'event_name'=>'discovery_opened','feature_key'=>'discovery',
             'platform'=>'demo','metadata'=>json_encode(['qa_demo'=>true,'not_real_activity'=>true]),
             'occurred_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
        );
    }
}
