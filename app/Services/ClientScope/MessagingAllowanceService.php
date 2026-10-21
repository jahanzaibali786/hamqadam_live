<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Only a paid plan with the unlimited_messaging flag bypasses the daily limit.
 * The limit applies to sends, not to reading or receiving replies.
 */
final class MessagingAllowanceService
{
    public function limit(): int
    {
        // A conservative configurable allowance; change in Admin Settings.
        return max(0, min(500, (int) get_setting('basic_daily_message_limit', 10)));
    }

    public function remaining(User $user): ?int
    {
        if (app(PremiumEntitlementService::class)->has($user, 'unlimited_messaging')) {
            return null;
        }
        $used = Chat::withTrashed()->where('sender_user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())->count();
        return max(0, $this->limit() - $used);
    }

    public function sendWithinLimit(User $user, callable $send): mixed
    {
        return DB::transaction(function () use ($user, $send) {
            // Serialise parallel sends for the same member. A check outside the
            // transaction allows multiple requests to overrun the last slot.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $this->assertMaySend($user);
            return $send();
        }, 3);
    }

    public function assertMaySend(User $user): void
    {
        if ($this->remaining($user) === 0) {
            throw ValidationException::withMessages([
                'message' => 'Your daily message allowance is exhausted. Upgrade to an active Unlimited Messaging plan.',
            ]);
        }
    }
}
