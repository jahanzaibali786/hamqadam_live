<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Services\ClientScope\OutcomeVerificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** A mutual confirmation nominates a pending case; it never verifies a marriage. */
final class QaMatchOutcomeNominationTest extends TestCase
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
        Schema::create('match_successes', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('matched_user_id'); $t->string('status'); $t->timestamps();
        });
        Schema::create('match_outcome_reviews', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('member_a_id');
            $t->unsignedBigInteger('member_b_id');
            $t->string('status')->default('pending');
            $t->string('verification_method')->nullable();
            $t->unsignedBigInteger('happy_story_id')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->text('internal_notes')->nullable(); $t->timestamps();
            $t->unique(['member_a_id', 'member_b_id']);
        });
        DB::table('match_successes')->insert([
            'user_id' => 18, 'matched_user_id' => 7, 'status' => 'got_match',
        ]);
    }

    public function test_one_sided_got_match_never_creates_a_review_candidate(): void
    {
        $this->assertNull(app(OutcomeVerificationService::class)->nominate(18, 7));
        $this->assertSame(0, DB::table('match_outcome_reviews')->count());
    }

    public function test_mutual_match_creates_one_pending_candidate_and_does_not_auto_verify(): void
    {
        DB::table('match_successes')->insert([
            'user_id' => 7, 'matched_user_id' => 18, 'status' => 'got_match',
        ]);
        $service = app(OutcomeVerificationService::class);
        $first = $service->nominate(18, 7);
        $this->assertNotNull($first);
        $this->assertSame('pending', $first->status);
        $this->assertNull($first->reviewed_by);
        $this->assertNull($first->reviewed_at);
        $this->assertSame($first->id, $service->nominate(7, 18)?->id);
        $this->assertDatabaseHas('match_outcome_reviews', [
            'member_a_id' => 7, 'member_b_id' => 18, 'status' => 'pending',
        ]);
        $this->assertSame(1, DB::table('match_outcome_reviews')->count());
    }

    public function test_self_nomination_is_disallowed(): void
    {
        $this->assertNull(app(OutcomeVerificationService::class)->nominate(7, 7));
    }
}

