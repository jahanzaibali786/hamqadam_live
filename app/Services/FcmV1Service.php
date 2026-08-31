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
    public static function sendData(string $fcmToken, array $data): bool
    {
        if (empty($fcmToken)) {
            return false;
        }

        $projectId = static::projectId();
        if ($projectId === '') {
            Log::error('FCM v1: FIREBASE_PROJECT_ID not configured.');
            return false;
        }

        $message = [
            'token' => $fcmToken,
            'data' => array_map('strval', $data),
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10'],
            ],
        ];

        return static::postMessage($projectId, $message);
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

            if (! static::postMessage($projectId, $message, $accessToken)) {
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
        return static::sendData($fcmToken, [
            'type' => 'call_incoming',
            'call_id' => (string) $callId,
            'call_type' => $callType,
            'caller_name' => $callerName,
        ]);
    }

    // ── Internal helpers ────────────────────────────────────────────────────

    private static function postMessage(string $projectId, array $message, ?string $accessToken = null): bool
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

            return false;
        } catch (\Throwable $e) {
            Log::error('FCM v1: Request failed.', ['error' => $e->getMessage()]);
            return false;
        }
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
