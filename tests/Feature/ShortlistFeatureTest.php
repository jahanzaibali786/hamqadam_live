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

    public function test_user_cannot_shortlist_without_mutual_interest_acceptance(): void
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
        $this->assertSame('0', (string) $response->content());
        $this->assertDatabaseMissing('shortlists', [
            'user_id' => $recipient->id,
            'shortlisted_by' => $sender->id,
        ]);
    }

    public function test_user_can_shortlist_only_after_both_sides_accept_interest(): void
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
        $this->assertSame(5, $senderMember->remaining_interest);
    }
}
