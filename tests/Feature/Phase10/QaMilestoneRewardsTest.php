<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Models\User;
use App\Services\ClientScope\EngagementMilestoneService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Milestone rewards require a real server-visible qualifying event. */
final class QaMilestoneRewardsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('members', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('user_id')->unique();
            $t->unsignedInteger('remaining_interest')->default(0);
            $t->unsignedInteger('profile_completion_percentage')->default(0);
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('reward_rules', function (Blueprint $t): void {
            $t->id(); $t->string('event_key')->unique(); $t->string('title');
            $t->unsignedInteger('coin_reward'); $t->unsignedInteger('max_per_user');
            $t->boolean('active'); $t->json('conditions')->nullable(); $t->timestamps();
        });
        Schema::create('reward_transactions', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('reward_rule_id')->nullable();
            $t->string('event_key'); $t->integer('coins');
            $t->string('reference_type')->nullable();
            $t->unsignedBigInteger('reference_id')->nullable();
            $t->json('metadata')->nullable(); $t->timestamps();
        });
        Schema::create('analytics_events', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('event_name'); $t->string('feature_key')->nullable();
            $t->string('source')->nullable(); $t->json('metadata')->nullable();
            $t->timestamp('occurred_at')->nullable(); $t->timestamps();
        });
        Schema::create('express_interests', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('interested_by');
        });
        DB::table('members')->insert([
            'id' => 17, 'user_id' => 8, 'remaining_interest' => 10,
            'profile_completion_percentage' => 75,
        ]);
        DB::table('reward_rules')->insert([
            ['event_key'=>'profile_completed','title'=>'QA completed','coin_reward'=>15,'max_per_user'=>1,'active'=>true],
            ['event_key'=>'first_interest','title'=>'QA first interest','coin_reward'=>5,'max_per_user'=>1,'active'=>true],
        ]);
        DB::table('express_interests')->insert(['id'=>103,'interested_by'=>8]);
    }

    private function user(): User
    {
        $user = new User();
        $user->id = 8;
        $user->approved = 1;
        $user->email_verified_at = now();
        return $user;
    }

    public function test_profile_reward_requires_fully_completed_verified_member_and_correct_reference(): void
    {
        $service = app(EngagementMilestoneService::class);
        $this->assertFalse($service->issue($this->user(), 'profile_completed', 17));
        DB::table('members')->where('id', 17)->update(['profile_completion_percentage'=>100]);
        $this->assertFalse($service->issue($this->user(), 'profile_completed', 999));
        $this->assertTrue($service->issue($this->user(), 'profile_completed', 17));
        $this->assertFalse($service->issue($this->user(), 'profile_completed', 17));
        $this->assertSame(25, (int) DB::table('members')->where('id',17)->value('remaining_interest'));
        $this->assertSame(1, DB::table('reward_transactions')->where('event_key','profile_completed')->count());
    }

    public function test_first_interest_reward_cannot_use_arbitrary_reference_and_is_once_per_user(): void
    {
        $service = app(EngagementMilestoneService::class);
        $this->assertFalse($service->issue($this->user(), 'first_interest', 999));
        $this->assertTrue($service->issue($this->user(), 'first_interest', 103));
        $this->assertFalse($service->issue($this->user(), 'first_interest', 103));
        DB::table('express_interests')->insert(['id'=>104, 'interested_by'=>8]);
        $this->assertFalse($service->issue($this->user(), 'first_interest', 104));
        $this->assertSame(15, (int) DB::table('members')->where('id',17)->value('remaining_interest'));
    }

    public function test_unapproved_member_cannot_claim_milestone(): void
    {
        DB::table('members')->where('id',17)->update(['profile_completion_percentage'=>100]);
        $user = $this->user(); $user->approved = 0;
        $this->assertFalse(app(EngagementMilestoneService::class)->issue($user,'profile_completed',17));
        $this->assertSame(0, DB::table('reward_transactions')->count());
    }
}

