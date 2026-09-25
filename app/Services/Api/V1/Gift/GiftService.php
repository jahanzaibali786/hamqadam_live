<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Gift;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\Gift;
use App\Models\GiftTransaction;
use App\Models\IgnoredUser;
use App\Models\PackageUsage;
use App\Models\User;
use App\Services\NotificationHelper;
use Illuminate\Support\Facades\DB;

/**
 * Gift sending on top of the EXISTING coin system: the balance lives in
 * members.remaining_interest (the same wallet proposals and shortlists spend),
 * so there is no second wallet. The gift price is ALWAYS loaded from the
 * gifts table inside the transaction — client-supplied coin values are ignored.
 */
class GiftService
{
    /** GET /gifts — active gifts, cheapest first. */
    public function list()
    {
        return Gift::where('is_active', true)->orderBy('sort_order')->get();
    }

    /** GET /gifts/{gift} — one gift's public data. */
    public function detail(Gift $gift): Gift
    {
        return $gift;
    }

    /**
     * POST /gifts/send — the atomic send. Locks the member row, re-checks the
     * balance inside the transaction (race protection), deducts the DB price,
     * records the usage transaction, and creates the gift row. Any failure
     * rolls everything back so coins are never lost.
     */
    public function send(User $sender, int $receiverId, int $giftId, ?string $message): array
    {
        if ((int) $sender->id === $receiverId) {
            throw new ApiException('You cannot send a gift to yourself.', 422, ApiErrorCode::ValidationFailed->value);
        }

        // The existing deactivation flag (users.deactivated) + soft deletes.
        $receiver = User::where('deactivated', 0)->find($receiverId);

        if (! $receiver) {
            throw new ApiException('Receiver not found.', 404, ApiErrorCode::NotFound->value);
        }

        // Respect the existing block/ignore rules.
        $ignored = IgnoredUser::where(function ($query) use ($sender, $receiverId): void {
            $query->where('ignored_by', $sender->id)->where('user_id', $receiverId);
        })->orWhere(function ($query) use ($sender, $receiverId): void {
            $query->where('ignored_by', $receiverId)->where('user_id', $sender->id);
        })->exists();

        if ($ignored) {
            throw new ApiException('This member is not available for gifts.', 403, ApiErrorCode::Forbidden->value);
        }

        $result = DB::transaction(function () use ($sender, $receiver, $giftId, $message): array {
            $gift = Gift::where('is_active', true)->lockForUpdate()->find($giftId);

            if (! $gift) {
                throw new ApiException('Gift not found or unavailable.', 404, ApiErrorCode::NotFound->value);
            }

            // Lock the wallet row — two simultaneous sends serialize here.
            $member = $sender->member()->lockForUpdate()->first();

            if (! $member) {
                throw new ApiException('Wallet not found for this account.', 402, 'wallet_missing');
            }

            $balance = (int) $member->remaining_interest;

            if ($balance < $gift->coins) {
                throw new ApiException(
                    'You need ' . $gift->coins . ' coin(s) to send this gift and you currently have ' . $balance . '.',
                    402,
                    'insufficient_coins'
                );
            }

            $member->remaining_interest = $balance - $gift->coins;
            $member->save();

            $transaction = GiftTransaction::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'gift_id' => $gift->id,
                'coins' => $gift->coins,
                'message' => $message !== null && $message !== '' ? mb_substr($message, 0, 300) : null,
                'status' => 'sent',
            ]);

            // The existing coin-usage ledger (same record proposals/shortlists write).
            PackageUsage::record(
                $sender->id,
                'gift_sent',
                'Gift: ' . $gift->name,
                $gift->coins,
                GiftTransaction::class,
                $transaction->id,
                'Sent ' . $gift->name . ' to user #' . $receiver->id . '.'
            );

            return [$transaction->load('gift'), $gift, $balance - $gift->coins];
        });

        [$transaction, $gift, $remaining] = $result;

        // Outside the transaction: a failed notification must not undo the gift.
        try {
            NotificationHelper::guardianEvent(
                $receiver,
                'gift_received',
                'You Received a Gift',
                trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? '')) . ' sent you a ' . $gift->name . ' (' . $gift->coins . ' coins).',
                $sender->id,
                $transaction->id,
                '/gifts/received',
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'gift_transaction_id' => $transaction->id,
            'gift' => $gift,
            'remaining_coins' => $remaining,
        ];
    }

    /** GET /gifts/received — gifts the caller received. */
    public function received(User $user, int $perPage = 30)
    {
        return GiftTransaction::with(['gift', 'sender:id,first_name,last_name'])
            ->where('receiver_id', $user->id)
            ->where('status', 'sent')
            ->latest()
            ->paginate(min($perPage, 50));
    }

    /** GET /gifts/sent — gifts the caller sent. */
    public function sent(User $user, int $perPage = 30)
    {
        return GiftTransaction::with(['gift', 'receiver:id,first_name,last_name'])
            ->where('sender_id', $user->id)
            ->where('status', 'sent')
            ->latest()
            ->paginate(min($perPage, 50));
    }

    /** GET /gifts/transactions/{transaction} — one gift detail for a participant. */
    public function transactionDetail(User $user, int $transactionId): GiftTransaction
    {
        $transaction = GiftTransaction::with(['gift', 'sender:id,first_name,last_name', 'receiver:id,first_name,last_name'])
            ->find($transactionId);

        if (! $transaction) {
            throw new ApiException('Gift not found.', 404, ApiErrorCode::NotFound->value);
        }

        if (! in_array($user->id, [(int) $transaction->sender_id, (int) $transaction->receiver_id], true)) {
            throw new ApiException('You do not have access to this gift.', 403, ApiErrorCode::Forbidden->value);
        }

        return $transaction;
    }
}
