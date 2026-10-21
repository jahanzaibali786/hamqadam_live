<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Models\User;
use App\Services\ClientScope\RewardIssuer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Exercises the actual reward issuer, with a deliberately minimal isolated SQLite schema. */
final class QaRewardIssuerTest extends TestCase
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
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('remaining_interest')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('reward_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('title');
            $table->unsignedInteger('coin_reward')->default(0);
            $table->unsignedInteger('max_per_user')->default(1);
            $table->boolean('active')->default(true);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_transactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('reward_rule_id')->nullable();
            $table->string('event_key');
            $table->integer('coins');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('match_successes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('matched_user_id');
            $table->string('status');
            $table->timestamps();
        });
        DB::table('members')->insert([
            ['id' => 11, 'user_id' => 1, 'remaining_interest' => 10],
            ['id' => 12, 'user_id' => 2, 'remaining_interest' => 10],
        ]);
        DB::table('reward_rules')->insert([
            'event_key' => 'got_match', 'title' => 'QA mutual match',
            'coin_reward' => 30, 'max_per_user' => 1, 'active' => 1,
        ]);
        DB::table('match_successes')->insert([
            'id' => 101, 'user_id' => 1, 'matched_user_id' => 2, 'status' => 'got_match',
        ]);
    }

    public function test_unilateral_match_issues_no_reward(): void
    {
        $this->assertFalse(app(RewardIssuer::class)->issue(1, 'got_match', 'match_success', 101));
        $this->assertSame(0, DB::table('reward_transactions')->count());
        $this->assertSame(10, (int) DB::table('members')->where('user_id', 1)->value('remaining_interest'));
    }

    public function test_mutual_match_rewards_once_and_caps_transactions(): void
    {
        DB::table('match_successes')->insert([
            'id' => 102, 'user_id' => 2, 'matched_user_id' => 1, 'status' => 'got_match',
        ]);
        $issuer = app(RewardIssuer::class);
        $this->assertTrue($issuer->issue(1, 'got_match', 'match_success', 101));
        $this->assertFalse($issuer->issue(1, 'got_match', 'match_success', 101));
        $this->assertSame(1, DB::table('reward_transactions')->count());
        $this->assertSame(40, (int) DB::table('members')->where('user_id', 1)->value('remaining_interest'));
        $this->assertTrue($issuer->issue(2, 'got_match', 'match_success', 102));
        $this->assertSame(2, DB::table('reward_transactions')->count());
    }

    public function test_wrong_reference_or_disabled_rule_cannot_issue_coins(): void
    {
        DB::table('match_successes')->insert([
            'id' => 102, 'user_id' => 2, 'matched_user_id' => 1, 'status' => 'got_match',
        ]);
        $issuer = app(RewardIssuer::class);
        $this->assertFalse($issuer->issue(1, 'got_match', 'arbitrary_event', 101));
        $this->assertFalse($issuer->issue(1, 'got_match', 'match_success', 102));
        DB::table('reward_rules')->where('event_key', 'got_match')->update(['active' => 0]);
        $this->assertFalse($issuer->issue(1, 'got_match', 'match_success', 101));
        $this->assertSame(0, DB::table('reward_transactions')->count());
    }
}

