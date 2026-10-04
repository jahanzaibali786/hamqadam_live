<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Notifications\EmailNotification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

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

    // ── Guardian Mode ───────────────────────────────────────────────────────

    /**
     * Generic guardian-mode notification (invitation sent/accepted, permission
     * changed, match actions, family introduction events, …). Never leaks
     * sensitive content: callers pass a short, already-safe message.
     */
    public static function guardianEvent(
        User $recipient,
        string $type,
        string $title,
        string $message,
        int $notifyBy,
        int $infoId,
        string $route = '/family',
    ): void {
        self::createAndPush(
            recipient: $recipient,
            type: $type,
            title: $title,
            message: mb_substr($message, 0, 200),
            notifyBy: $notifyBy,
            infoId: $infoId,
            route: $route,
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
        $eventKey = self::legacyEventKey($type) ?? (config("notification_events.events.$type") ? $type : null);
        if ($eventKey !== null && config("notification_events.events.$eventKey")) {
            self::event($recipient, $eventKey, [
                'event_id' => (string) $infoId,
                'notify_by' => $notifyBy,
                'info_id' => $infoId,
                'name' => $title,
                'message' => $message,
                'skip_push' => ! $push,
            ]);

            return;
        }

        // Keep older callers safe while they are being migrated to the
        // catalog: preserve their in-app/push behaviour, but use the UUID,
        // dedupe, and delivery-log aware writer.
        self::deliver(
            $recipient,
            $type,
            (string) $infoId,
            $title,
            $message,
            $route,
            $notifyBy,
            ['info_id' => $infoId, 'notify_by' => $notifyBy, 'skip_push' => ! $push],
            ['channels' => $push ? ['push'] : [], 'critical' => false],
        );

        return;

        try {
            $preferences = NotificationPreference::firstOrCreate(['user_id' => $recipient->id]);
            $eventPreference = (array) ($preferences->event_preferences ?? []);
            $eventEnabled = ! array_key_exists($type, $eventPreference) || (bool) $eventPreference[$type];

            if (! $eventEnabled) {
                return;
            }

            // 1. Store in database (Laravel's notification table)
            // NOTE: id column is bigint auto-increment — do NOT set it manually.
            // Use raw DB insert to avoid Eloquent double-encoding the data column.
            $notificationId = null;
            if ($preferences->in_app_enabled) {
                $notificationId = DB::table('notifications')->insertGetId([
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
                NotificationDeliveryLog::create([
                    'notification_id' => (string) $notificationId,
                    'user_id' => $recipient->id,
                    'channel' => 'in_app',
                    'status' => 'sent',
                    'payload' => ['type' => $type, 'route' => $route],
                    'sent_at' => now(),
                ]);
            }

            // 2. Send the FCM v1 push, to every device this member has.
            //
            // $push is false for chat messages: ChatApiService has already sent
            // one that carries `message_id`, and two pushes for one message
            // means Android draws two tray entries when the app is in the
            // background - which the app cannot de-duplicate, because a
            // notification-block push is drawn by the system before any app
            // code runs.
            if ($push && $preferences->push_enabled && ! self::isQuietHours($preferences)) {
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
                    NotificationDeliveryLog::create([
                        'notification_id' => $notificationId ? (string) $notificationId : null,
                        'user_id' => $recipient->id,
                        'channel' => 'push',
                        'status' => 'sent',
                        'payload' => ['type' => $type, 'route' => $route],
                        'sent_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    NotificationDeliveryLog::create([
                        'notification_id' => $notificationId ? (string) $notificationId : null,
                        'user_id' => $recipient->id,
                        'channel' => 'push',
                        'status' => 'failed',
                        'error_message' => mb_substr($e->getMessage(), 0, 1000),
                        'payload' => ['type' => $type, 'route' => $route],
                    ]);
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

    /**
     * Publish one catalogued event through the channels defined in the
     * notification matrix. Context values may replace :placeholders in copy.
     */
    public static function event(User $recipient, string $eventKey, array $context = []): void
    {
        $definition = config("notification_events.events.$eventKey");
        if (! is_array($definition)) {
            Log::warning('Unknown notification event.', ['event_key' => $eventKey]);
            return;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $replace[':' . $key] = (string) $value;
            }
        }

        $message = strtr((string) ($definition['message'] ?? ''), $replace);
        $title = strtr((string) ($definition['title'] ?? ''), $replace);
        $deepLink = (string) ($context['deep_link'] ?? $definition['route'] ?? '/dashboard');
        $eventId = (string) ($context['event_id'] ?? $context['info_id'] ?? sha1($eventKey . '|' . $recipient->id . '|' . $message));
        $notifyBy = (int) ($context['notify_by'] ?? 0);

        self::deliver($recipient, $eventKey, $eventId, $title, $message, $deepLink, $notifyBy, $context, $definition);
    }

    private static function deliver(
        User $recipient,
        string $eventKey,
        string $eventId,
        string $title,
        string $message,
        string $deepLink,
        int $notifyBy,
        array $context,
        array $definition,
    ): void {
        try {
            $preferences = NotificationPreference::firstOrCreate(['user_id' => $recipient->id]);
            $critical = (bool) ($definition['critical'] ?? false);
            $eventPreferences = (array) ($preferences->event_preferences ?? []);
            if (! $critical && array_key_exists($eventKey, $eventPreferences) && ! (bool) $eventPreferences[$eventKey]) {
                return;
            }

        $channels = (array) ($definition['channels'] ?? []);
        // An empty channel list is intentional for silent product events such
        // as profile views and shortlists.
        if ($channels === []) {
            return;
        }
            $notificationId = null;
            if ($preferences->in_app_enabled && ! self::alreadyDelivered($recipient->id, $eventKey, $eventId, 'in_app')) {
                $notificationId = (string) Str::uuid();
                DB::table('notifications')->insert([
                    'id' => $notificationId,
                    'type' => $eventKey,
                    'category' => self::categoryFor($eventKey),
                    'notifiable_type' => User::class,
                    'notifiable_id' => $recipient->id,
                    'data' => json_encode([
                        'event_key' => $eventKey,
                        'type' => $eventKey,
                        'title' => $title,
                        'message' => $message,
                        'notify_by' => $notifyBy,
                        'info_id' => $context['info_id'] ?? null,
                        'route' => $deepLink,
                        'deep_link' => $deepLink,
                        'payload' => $context,
                    ], JSON_UNESCAPED_UNICODE),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                self::logDelivery($recipient, $notificationId, $eventKey, $eventId, 'in_app', 'sent', $deepLink, $context);
            }

            $quiet = self::isQuietHours($preferences);
            if (in_array('push', $channels, true) && ! ($context['skip_push'] ?? false) && ($critical || ($preferences->push_enabled && ! $quiet)) && ! self::alreadyDelivered($recipient->id, $eventKey, $eventId, 'push')) {
                try {
                    FcmV1Service::sendToUser((int) $recipient->id, ['title' => $title, 'body' => $message], [
                        'type' => $eventKey,
                        'event_key' => $eventKey,
                        'event_id' => $eventId,
                        'notify_by' => (string) $notifyBy,
                        'info_id' => (string) ($context['info_id'] ?? ''),
                        'route' => $deepLink,
                        'deep_link' => $deepLink,
                    ]);
                    self::logDelivery($recipient, $notificationId, $eventKey, $eventId, 'push', 'sent', $deepLink, $context);
                } catch (\Throwable $e) {
                    self::logDelivery($recipient, $notificationId, $eventKey, $eventId, 'push', 'failed', $deepLink, $context, $e->getMessage());
                    Log::warning('FCM push failed for notification.', ['user_id' => $recipient->id, 'event_key' => $eventKey, 'error' => $e->getMessage()]);
                }
            }

            $emailRequired = in_array('email', $channels, true);
            $emailOptional = in_array('email_optional', $channels, true);
            if (($emailRequired || ($emailOptional && $preferences->email_enabled)) && ! self::alreadyDelivered($recipient->id, $eventKey, $eventId, 'email') && filled($recipient->email)) {
                try {
                    NotificationFacade::route('mail', $recipient->email)->notify(new EmailNotification(
                        $title,
                        '<p>' . e($message) . '</p><p><a href="' . e(url($deepLink)) . '">Open Hamqadam</a></p>',
                    ));
                    self::logDelivery($recipient, $notificationId, $eventKey, $eventId, 'email', 'sent', $deepLink, $context);
                } catch (\Throwable $e) {
                    self::logDelivery($recipient, $notificationId, $eventKey, $eventId, 'email', 'failed', $deepLink, $context, $e->getMessage());
                    Log::warning('Notification email failed.', ['user_id' => $recipient->id, 'event_key' => $eventKey, 'error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to deliver notification event.', ['user_id' => $recipient->id, 'event_key' => $eventKey, 'error' => $e->getMessage()]);
        }
    }

    private static function alreadyDelivered(int $userId, string $eventKey, string $eventId, string $channel): bool
    {
        return NotificationDeliveryLog::query()
            ->where('user_id', $userId)
            ->where('event_key', $eventKey)
            ->where('event_id', $eventId)
            ->where('channel', $channel)
            ->whereIn('status', ['queued', 'sent', 'delivered', 'read'])
            ->exists();
    }

    private static function logDelivery(User $recipient, ?string $notificationId, string $eventKey, string $eventId, string $channel, string $status, string $deepLink, array $context, ?string $error = null): void
    {
        NotificationDeliveryLog::create([
            'notification_id' => $notificationId,
            'user_id' => $recipient->id,
            'channel' => $channel,
            'event_key' => $eventKey,
            'event_id' => $eventId,
            'status' => $status,
            'error_message' => $error ? mb_substr($error, 0, 1000) : null,
            'failure_reason' => $error ? mb_substr($error, 0, 1000) : null,
            'payload' => $context,
            'deep_link' => $deepLink,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    private static function legacyEventKey(string $type): ?string
    {
        return [
            'interest' => 'interest_received',
            'interest_accepted' => 'interest_accepted',
            'interest_rejected' => 'interest_declined',
            'proposal' => 'proposal_received',
            'proposal_accepted' => 'proposal_accepted',
            'proposal_rejected' => 'proposal_declined',
            'call_missed' => 'missed_audio_call',
            'chat_message' => 'chat_message',
            'profile_view' => 'profile_viewed',
            'shortlist' => 'profile_shortlisted',
            'guardian' => 'guardian_action_required',
        ][$type] ?? null;
    }

    private static function categoryFor(string $eventKey): string
    {
        return match (true) {
            str_starts_with($eventKey, 'account_'), str_contains($eventKey, 'login'), str_contains($eventKey, 'password'), str_contains($eventKey, 'security') => 'security',
            str_contains($eventKey, 'verification'), str_contains($eventKey, 'photo_'), str_contains($eventKey, 'identity_'), str_contains($eventKey, 'reverification') => 'verification',
            str_contains($eventKey, 'match'), str_contains($eventKey, 'recommendation'), str_contains($eventKey, 'suggested'), str_contains($eventKey, 'preference_'), str_contains($eventKey, 'profile_view'), str_contains($eventKey, 'shortlisted') => 'discovery',
            str_contains($eventKey, 'interest') => 'interest',
            str_contains($eventKey, 'proposal') => 'proposal',
            str_contains($eventKey, 'chat'), str_contains($eventKey, 'message'), str_contains($eventKey, 'call'), str_contains($eventKey, 'conversation') => 'chat',
            str_contains($eventKey, 'guardian'), str_contains($eventKey, 'family') => 'family',
            str_contains($eventKey, 'subscription'), str_contains($eventKey, 'payment'), str_contains($eventKey, 'refund'), str_contains($eventKey, 'plan_') => 'payment',
            str_contains($eventKey, 'referral'), str_contains($eventKey, 'reward'), str_contains($eventKey, 'achievement'), str_contains($eventKey, 'milestone') => 'rewards',
            str_contains($eventKey, 'support'), str_contains($eventKey, 'safety') => 'support',
            default => 'general',
        };
    }

    private static function isQuietHours(NotificationPreference $preferences): bool
    {
        if (! $preferences->quiet_hours_start || ! $preferences->quiet_hours_end) {
            return false;
        }

        $now = Carbon::now($preferences->timezone ?: config('app.timezone'))->format('H:i:s');
        $start = (string) $preferences->quiet_hours_start;
        $end = (string) $preferences->quiet_hours_end;

        return $start <= $end
            ? $now >= $start && $now < $end
            : $now >= $start || $now < $end;
    }
}
