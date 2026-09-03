<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Bridge for the eight controllers that still call `FirbaseNotification::send`.
 *
 * This used to POST to `https://fcm.googleapis.com/fcm/send`, the legacy FCM
 * endpoint Google retired in June 2024, with an `Authorization: key=` server
 * key. It also threw the result away, so every one of those notifications -
 * express interest, profile-picture and gallery view requests, profile views,
 * package payment approvals - reached nobody, and nothing was written to the
 * log to say so.
 *
 * It now hands the same payload to FCM v1. Where the token can be traced back
 * to a member the push is addressed to the member instead, so it reaches every
 * device they have signed in on (`user_push_tokens`) rather than only whichever
 * one last wrote the shared `users.fcm_token` column.
 */
class FirbaseNotification
{
    public static function send($data)
    {
        $token = (string) ($data->fcm_token ?? '');
        if ($token === '') {
            return;
        }

        $type = (string) ($data->title ?? '');
        $body = (string) ($data->text ?? '');

        $notification = [
            'title' => str_replace('_', ' ', $type),
            'body'  => $body,
        ];

        // The keys the app routes and de-duplicates activity pushes on.
        $payload = [
            'type'         => $type,
            'route'        => $type,
            'notify_by'    => (string) ($data->notify_by ?? ''),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        try {
            $userId = User::where('fcm_token', $token)->value('id');

            if ($userId) {
                FcmV1Service::sendToUser((int) $userId, $notification, $payload);
                return;
            }

            FcmV1Service::send($token, $notification, $payload);
        } catch (\Throwable $e) {
            Log::warning('FCM v1 push failed.', [
                'type'  => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
