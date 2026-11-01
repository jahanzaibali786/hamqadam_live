<?php

namespace Tests\Feature;

use App\Models\ExpressInterest;
use App\Models\Member;
use App\Models\Shortlist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortlistFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_shortlist_without_interest_or_package_coins(): void
    {
        $sender = User::factory()->create([
            'user_type' => 'member',
            'approved' => 1,
        ]);
        $senderMember = Member::create([
            'user_id' => $sender->id,
            'remaining_interest' => 10,
            'package_validity' => now()->addDay()->toDateString(),
        ]);

        $recipient = User::factory()->create([
            'user_type' => 'member',
            'approved' => 1,
        ]);
        $recipientMember = Member::create([
            'user_id' => $recipient->id,
            'remaining_interest' => 10,
            'package_validity' => now()->addDay()->toDateString(),
        ]);

        $response = $this
            ->actingAs($sender)
            ->post('/member/add-to-shortlist', ['id' => $recipient->id]);

        $response->assertOk();
        $this->assertSame('1', (string) $response->content());
        $this->assertDatabaseHas('shortlists', [
            'user_id' => $recipient->id,
            'shortlisted_by' => $sender->id,
        ]);
    }

    public function test_user_can_shortlist_after_accepted_interest_without_spending_coins(): void
    {
        $sender = User::factory()->create([
            'user_type' => 'member',
            'approved' => 1,
        ]);
        $senderMember = Member::create([
            'user_id' => $sender->id,
            'remaining_interest' => 10,
            'package_validity' => now()->addDay()->toDateString(),
        ]);

        $recipient = User::factory()->create([
            'user_type' => 'member',
            'approved' => 1,
        ]);
        $recipientMember = Member::create([
            'user_id' => $recipient->id,
            'remaining_interest' => 10,
            'package_validity' => now()->addDay()->toDateString(),
        ]);

        ExpressInterest::create([
            'user_id' => $recipient->id,
            'interested_by' => $sender->id,
            'status' => 1,
        ]);

        ExpressInterest::create([
            'user_id' => $sender->id,
            'interested_by' => $recipient->id,
            'status' => 1,
        ]);

        $response = $this
            ->actingAs($sender)
            ->post('/member/add-to-shortlist', ['id' => $recipient->id]);

        $response->assertOk();
        $this->assertSame('1', (string) $response->content());
        $this->assertDatabaseHas('shortlists', [
            'user_id' => $recipient->id,
            'shortlisted_by' => $sender->id,
        ]);
        $senderMember->refresh();
        $this->assertSame(10, $senderMember->remaining_interest);
    }
}
