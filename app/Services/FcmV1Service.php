<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging v1 API client.
 *
 * The legacy `https://fcm.googleapis.com/fcm/send` was shut down by Google
 * on 20 June 2024.  This service uses the current v1 REST endpoint:
 *
 *   POST https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
 *
 * Authentication is done via an OAuth2 access token generated from a
 * Firebase service-account JSON key.  The token is cached for 55 minutes
 * (tokens expire after 60 min) so the key file is read only once per hour.
 *
 * Setup:
 *   1. Place the Firebase service-account JSON in `storage/app/firebase-service-account.json`
 *      (or set FIREBASE_SERVICE_ACCOUNT_PATH in .env).
 *   2. Set FIREBASE_PROJECT_ID in .env (or it will be read from the JSON).
 *
 * Usage:
 *   FcmV1Service::send($fcmToken, ['title' => '...', 'body' => '...']);
 *   FcmV1Service::sendData($fcmToken, ['type' => 'call_incoming', 'call_id' => '42']);
 */
class FcmV1Service
{
    private const TOKEN_CACHE_KEY = 'fcm_v1_access_token';
    private const TOKEN_CACHE_TTL = 3300; // 55 minutes

    /**
     * Send a notification + data message to a single FCM token.
     *
     * @param  string  $fcmToken  The device's FCM registration token.
     * @param  array{title: string, body: string}  $notification
     * @param  array<string, string>  $data  Extra data payload (all values must be strings).
     * @return bool  true on success.
     */
    public static function send(string $fcmToken, array $notification, array $data = []): bool
    {
        return static::sendToTokens([$fcmToken], $notification, $data);
    }

    /**
     * Send a data-only message (no notification tray entry).
     * Used for call signals that should be handled silently by the app.
     */
    public static function sendData(string $fcmToken, array $data, ?array $apnsAlert = null): bool
    {
        if (empty($fcmToken)) {
            return false;
        }

        $projectId = static::projectId();
        if ($projectId === '') {
            Log::error('FCM v1: FIREBASE_PROJECT_ID not configured.');
            return false;
        }

        // APNs is strict about data-only messages in a way FCM is not, and
        // getting it wrong is silent: this used to send `apns-priority: 10`
        // with no `apns-push-type` at all, which APNs rejects, so no data push
        // ever reached an iPhone.
        //
        // Android is unaffected either way and stays data-only on purpose - the
        // app has to draw an incoming call itself to get the Accept/Decline
        // actions and the full-screen ring.
        if ($apnsAlert === null) {
            // A genuinely silent push (e.g. "this call is over"). Background
            // pushes must be priority 5; APNs refuses 10 for them.
            $apns = [
                'headers' => [
                    'apns-push-type' => 'background',
                    'apns-priority' => '5',
                ],
                'payload' => ['aps' => ['content-available' => 1]],
            ];
        } else {
            // An incoming call. iOS cannot raise a ringing screen from a push
            // without CallKit, so it gets a time-sensitive alert carrying the
            // Accept / Decline category the app registers instead.
            $apns = [
                'headers' => [
                    'apns-push-type' => 'alert',
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => (string) ($apnsAlert['title'] ?? 'HamQadam'),
                            'body' => (string) ($apnsAlert['body'] ?? ''),
                        ],
                        'sound' => 'default',
                        'category' => 'hamqadam_call',
                        'interruption-level' => 'time-sensitive',
                        'mutable-content' => 1,
                    ],
                ],
            ];
        }

        $message = [
            'token' => $fcmToken,
            'data' => array_map('strval', $data),
            'android' => [
                'priority' => 'high',
            ],
            'apns' => $apns,
        ];

        return static::postMessage($projectId, $message, null, $fcmToken);
    }

    /**
     * Send to multiple FCM tokens in a single batch.
     */
    public static function sendToTokens(array $tokens, array $notification, array $data = []): bool
    {
        $projectId = static::projectId();
        if ($projectId === '') {
            Log::error('FCM v1: FIREBASE_PROJECT_ID not configured.');
            return false;
        }

        $accessToken = static::accessToken();
        if ($accessToken === '') {
            Log::error('FCM v1: Could not generate access token.');
            return false;
        }

        $success = true;

        foreach ($tokens as $token) {
            if (empty($token)) continue;

            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $notification['title'] ?? 'HamQadam',
                    'body' => $notification['body'] ?? '',
                ],
                'data' => array_map('strval', $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'headers' => ['apns-priority' => '10'],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ];

            if (! static::postMessage($projectId, $message, $accessToken, $token)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send a high-priority data message for incoming calls.
     * This bypasses notification trays and goes straight to the app's
     * data handler, which shows the custom incoming-call UI.
     */
    public static function sendCallPush(string $fcmToken, int $callId, string $callType, string $callerName): bool
    {
        return static::sendData(
            $fcmToken,
            [
                'type' => 'call_incoming',
                'call_id' => (string) $callId,
                'call_type' => $callType,
                'caller_name' => $callerName,
            ],
            static::callAlert($callType, $callerName),
        );
    }

    // ── Internal helpers ────────────────────────────────────────────────────

    private static function postMessage(string $projectId, array $message, ?string $accessToken = null, ?string $token = null): bool
    {
        $accessToken ??= static::accessToken();
        if ($accessToken === '') return false;

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, ['message' => $message]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM v1: API returned error.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($token !== null) {
                static::forgetDeadToken($token, $response->status(), (string) $response->body());
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('FCM v1: Request failed.', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Drops a registration token Google has told us is unusable.
     *
     * Without this, one bad token silences a member's phone for good: the app
     * keeps working over the websocket while it is open and goes completely
     * quiet once it is closed, with nothing but a 400 in this log to say why.
     *
     * Two cases are worth pruning:
     *
     *  - 404 UNREGISTERED - the app was uninstalled, or the token rotated.
     *  - 400 "not a valid FCM registration token" - the stored value is not a
     *    token at all. That is what POST /fcm-token writes when the website's
     *    Firebase JS config is empty, and it lands in the very same
     *    users.fcm_token column the mobile app registers into.
     */
    private static function forgetDeadToken(string $token, int $status, string $body): void
    {
        $dead = $status === 404
            || ($status === 400 && str_contains($body, 'not a valid FCM registration token'))
            || str_contains($body, 'UNREGISTERED')
            || str_contains($body, 'INVALID_ARGUMENT');

        if (! $dead || $token === '') {
            return;
        }

        try {
            $rows = \App\Models\UserPushToken::where('token', $token)->delete();
            $cols = \App\Models\User::where('fcm_token', $token)->update(['fcm_token' => null]);
            Log::info('FCM v1: pruned a dead registration token.', [
                'status' => $status,
                'push_token_rows' => $rows,
                'user_columns' => $cols,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM v1: could not prune a dead token.', ['error' => $e->getMessage()]);
        }
    }

    // -- Per-user delivery ---------------------------------------------------

    /**
     * Every token this member can be reached on.
     *
     * user_push_tokens is the real register - the mobile app writes a row per
     * device - but every sender used to read users.fcm_token instead, a single
     * column that the app AND the website both write. Whoever wrote last won,
     * so a member who opened the site on a desktop stopped being reachable on
     * their phone. Reading both, de-duplicated, is what lets the phone and the
     * browser be notified at the same time.
     *
     * @return list<string>
     */
    public static function tokensForUser(int $userId): array
    {
        $tokens = [];

        try {
            $tokens = \App\Models\UserPushToken::query()
                ->where('user_id', $userId)
                ->pluck('token')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('FCM v1: could not read user_push_tokens.', ['error' => $e->getMessage()]);
        }

        // The legacy column: the website only ever writes here, and so did app
        // installs from before the table existed.
        try {
            $legacy = \App\Models\User::query()->whereKey($userId)->value('fcm_token');
            if (is_string($legacy) && $legacy !== '') {
                $tokens[] = $legacy;
            }
        } catch (\Throwable $e) {
            Log::warning('FCM v1: could not read users.fcm_token.', ['error' => $e->getMessage()]);
        }

        $tokens = array_filter($tokens, static fn ($t) => is_string($t) && $t !== '');

        return array_values(array_unique($tokens));
    }

    /**
     * Notification + data to every device a member has.
     */
    public static function sendToUser(int $userId, array $notification, array $data = []): bool
    {
        $tokens = static::tokensForUser($userId);
        if ($tokens === []) {
            Log::info('FCM v1: no registration token for user.', ['user_id' => $userId]);
            return false;
        }

        return static::sendToTokens($tokens, $notification, $data);
    }

    /**
     * Data-only to every device a member has. Used for call signals, which the
     * app has to draw itself.
     */
    public static function sendDataToUser(int $userId, array $data, ?array $apnsAlert = null): bool
    {
        $tokens = static::tokensForUser($userId);
        if ($tokens === []) {
            Log::info('FCM v1: no registration token for user.', ['user_id' => $userId]);
            return false;
        }

        $sent = false;
        foreach ($tokens as $token) {
            $sent = static::sendData($token, $data, $apnsAlert) || $sent;
        }

        return $sent;
    }

    /**
     * Rings every device a member has; they answer on whichever they pick up.
     */
    public static function sendCallPushToUser(int $userId, int $callId, string $callType, string $callerName): bool
    {
        return static::sendDataToUser(
            $userId,
            [
                'type' => 'call_incoming',
                'call_id' => (string) $callId,
                'call_type' => $callType,
                'caller_name' => $callerName,
            ],
            static::callAlert($callType, $callerName),
        );
    }

    /**
     * What an iPhone shows for an incoming call. Android ignores it and draws
     * its own ringing notification from the data payload.
     *
     * @return array{title: string, body: string}
     */
    private static function callAlert(string $callType, string $callerName): array
    {
        return [
            'title' => $callerName !== '' ? $callerName : 'HamQadam',
            'body' => $callType === 'video' ? 'Incoming video call' : 'Incoming voice call',
        ];
    }

    /**
     * Tells every device a call is over, so a ringing notification on a
     * sleeping phone is dismissed instead of ringing out its whole window.
     *
     * $type is call_cancelled / call_ended / call_rejected / call_missed.
     */
    public static function sendCallEndedToUser(int $userId, int $callId, string $type): bool
    {
        return static::sendDataToUser($userId, [
            'type' => $type,
            'call_id' => (string) $callId,
        ]);
    }

    /**
     * Generate a short-lived OAuth2 access token from the service-account key.
     * Cached for 55 minutes to avoid reading the JSON + JWT signing on every push.
     */
    private static function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, self::TOKEN_CACHE_TTL, function () {
            return static::generateAccessToken();
        }) ?: '';
    }

    private static function generateAccessToken(): string
    {
        $keyPath = static::serviceAccountPath();
        if (! file_exists($keyPath)) {
            Log::error('FCM v1: Service account JSON not found.', ['path' => $keyPath]);
            return '';
        }

        $serviceAccount = json_decode(file_get_contents($keyPath), true);
        if (! is_array($serviceAccount)) {
            Log::error('FCM v1: Invalid service account JSON.');
            return '';
        }

        $now = time();
        $jwtPayload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $jwt = JWT::encode($jwtPayload, $serviceAccount['private_key'], 'RS256');

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token', '');
            }

            Log::error('FCM v1: Token exchange failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM v1: Token exchange error.', ['error' => $e->getMessage()]);
        }

        return '';
    }

    private static function projectId(): string
    {
        return (string) (config('services.firebase.project_id', '') ?: env('FIREBASE_PROJECT_ID', ''));
    }

    private static function serviceAccountPath(): string
    {
        return (string) (env('FIREBASE_SERVICE_ACCOUNT_PATH', storage_path('app/firebase-service-account.json')));
    }
}
