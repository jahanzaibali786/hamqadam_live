<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Creates notification records in the database AND sends FCM v1 push
 * notifications. Every service that needs to notify a user should call
 * one of the static convenience methods here instead of duplicating logic.
 *
 * Usage:
 *   NotificationHelper::chatMessage($recipient, $sender, $threadId, $preview);
 *   NotificationHelper::interestReceived($recipient, $sender, $interestId);
 *   NotificationHelper::callMissed($recipient, $caller, $callId);
 *   // etc.
 */
class NotificationHelper
{
    // ── Chat ───────────────────────────────────────────────────────────────

    public static function chatMessage(
        User $recipient,
        User $sender,
        int $threadId,
        string $preview,
    ): void {
        $senderName = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'chat_message',
            title: $senderName,
            message: mb_substr($preview, 0, 200),
            notifyBy: $sender->id,
            infoId: $threadId,
            route: "/chat/$threadId",
            // ChatApiService::sendChatFcmPush already pushed this message, and
            // its payload is the one with `message_id`.
            push: false,
        );
    }

    // ── Interest ───────────────────────────────────────────────────────────

    public static function interestReceived(
        User $recipient,
        User $sender,
        int $interestId,
    ): void {
        $senderName = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'interest',
            title: 'New Interest',
            message: "$senderName is interested in you!",
            notifyBy: $sender->id,
            infoId: $interestId,
            route: '/interests',
        );
    }

    public static function interestAccepted(
        User $recipient,
        User $accepter,
        int $interestId,
    ): void {
        $name = trim(($accepter->first_name ?? '') . ' ' . ($accepter->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'interest_accepted',
            title: 'Interest Accepted',
            message: "$name accepted your interest!",
            notifyBy: $accepter->id,
            infoId: $interestId,
            route: '/interests',
        );
    }

    public static function interestRejected(
        User $recipient,
        User $rejecter,
        int $interestId,
    ): void {
        $name = trim(($rejecter->first_name ?? '') . ' ' . ($rejecter->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'interest_rejected',
            title: 'Interest Declined',
            message: "$name declined your interest.",
            notifyBy: $rejecter->id,
            infoId: $interestId,
            route: '/interests',
        );
    }

    // ── Proposal ───────────────────────────────────────────────────────────

    public static function proposalReceived(
        User $recipient,
        User $sender,
        int $proposalId,
    ): void {
        $senderName = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'proposal',
            title: 'New Proposal',
            message: "$senderName sent you a proposal!",
            notifyBy: $sender->id,
            infoId: $proposalId,
            route: '/proposals',
        );
    }

    public static function proposalAccepted(
        User $recipient,
        User $accepter,
        int $proposalId,
    ): void {
        $name = trim(($accepter->first_name ?? '') . ' ' . ($accepter->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'proposal_accepted',
            title: 'Proposal Accepted',
            message: "$name accepted your proposal!",
            notifyBy: $accepter->id,
            infoId: $proposalId,
            route: '/proposals',
        );
    }

    public static function proposalRejected(
        User $recipient,
        User $rejecter,
        int $proposalId,
    ): void {
        $name = trim(($rejecter->first_name ?? '') . ' ' . ($rejecter->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'proposal_rejected',
            title: 'Proposal Declined',
            message: "$name declined your proposal.",
            notifyBy: $rejecter->id,
            infoId: $proposalId,
            route: '/proposals',
        );
    }

    // ── Profile View ───────────────────────────────────────────────────────

    public static function profileViewed(
        User $owner,
        User $viewer,
        int $viewerId,
    ): void {
        $viewerName = trim(($viewer->first_name ?? '') . ' ' . ($viewer->last_name ?? ''));

        self::createAndPush(
            recipient: $owner,
            type: 'profile_view',
            title: 'Profile View',
            message: "$viewerName viewed your profile.",
            notifyBy: $viewerId,
            infoId: $viewerId,
            route: '/profile-views',
        );
    }

    // ── Calls ──────────────────────────────────────────────────────────────

    public static function callMissed(
        User $recipient,
        User $caller,
        int $callId,
    ): void {
        $callerName = trim(($caller->first_name ?? '') . ' ' . ($caller->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'call_missed',
            title: 'Missed Call',
            message: "Missed call from $callerName",
            notifyBy: $caller->id,
            infoId: $callId,
            route: '/calls',
        );
    }

    // ── Coins / Payments ───────────────────────────────────────────────────

    public static function coinsUsed(
        User $recipient,
        string $feature,
        int $coinsSpent,
    ): void {
        self::createAndPush(
            recipient: $recipient,
            type: 'coin_usage',
            title: 'Coins Used',
            message: "$coinsSpent coins used for $feature.",
            notifyBy: $recipient->id,
            infoId: 0,
            route: '/coins',
        );
    }

    public static function coinsReceived(
        User $recipient,
        int $coins,
        string $reason,
    ): void {
        self::createAndPush(
            recipient: $recipient,
            type: 'coin_received',
            title: 'Coins Received',
            message: "You received $coins coins. $reason",
            notifyBy: $recipient->id,
            infoId: 0,
            route: '/coins',
        );
    }

    // ── Shortlist ──────────────────────────────────────────────────────────

    public static function shortlisted(
        User $recipient,
        User $by,
    ): void {
        $name = trim(($by->first_name ?? '') . ' ' . ($by->last_name ?? ''));

        self::createAndPush(
            recipient: $recipient,
            type: 'shortlist',
            title: 'Shortlisted',
            message: "$name shortlisted your profile!",
            notifyBy: $by->id,
            infoId: $by->id,
            route: '/shortlists',
        );
    }

    // ── Core: Create DB record + send FCM push ─────────────────────────────

    /**
     * Creates a notification record in the `notifications` table AND sends
     * an FCM v1 push notification to the recipient's device.
     */
    private static function createAndPush(
        User $recipient,
        string $type,
        string $title,
        string $message,
        int $notifyBy,
        int $infoId,
        string $route,
        bool $push = true,
    ): void {
        try {
            // 1. Store in database (Laravel's notification table)
            // NOTE: id column is bigint auto-increment — do NOT set it manually.
            // Use raw DB insert to avoid Eloquent double-encoding the data column.
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'type' => $type,
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $recipient->id,
                'data' => json_encode([
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'notify_by' => $notifyBy,
                    'info_id' => $infoId,
                    'route' => $route,
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Send the FCM v1 push, to every device this member has.
            //
            // $push is false for chat messages: ChatApiService has already sent
            // one that carries `message_id`, and two pushes for one message
            // means Android draws two tray entries when the app is in the
            // background - which the app cannot de-duplicate, because a
            // notification-block push is drawn by the system before any app
            // code runs.
            if ($push) {
                try {
                    FcmV1Service::sendToUser(
                        (int) $recipient->id,
                        ['title' => $title, 'body' => $message],
                        [
                            'type' => $type,
                            'notify_by' => (string) $notifyBy,
                            'info_id' => (string) $infoId,
                            'route' => $route,
                        ],
                    );
                } catch (\Throwable $e) {
                    Log::warning('FCM push failed for notification.', [
                        'user_id' => $recipient->id,
                        'type' => $type,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to create notification record.', [
                'user_id' => $recipient->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
