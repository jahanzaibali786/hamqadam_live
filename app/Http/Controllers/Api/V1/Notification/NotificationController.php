<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Notification\NotificationListRequest;
use App\Http\Requests\Api\V1\Notification\StorePushTokenRequest;
use App\Http\Requests\Api\V1\Notification\UpdateNotificationPreferencesRequest;
use App\Http\Resources\Api\V1\Notification\NotificationPreferenceResource;
use App\Http\Resources\Api\V1\Notification\NotificationResource;
use App\Services\Api\V1\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function index(NotificationListRequest $request): JsonResponse
    {
        return NotificationResource::collection(
            $this->notifications->list($request->user(), $request->validated())
        )->additional([
            'success' => true,
            'meta' => ['unread_count' => $this->notifications->unreadCount($request->user())],
        ])->response();
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success(['unread_count' => $this->notifications->unreadCount($request->user())]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        return $this->success(
            new NotificationResource($this->notifications->markRead($request->user(), $notification)),
            'Notification marked as read.'
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $updated = $this->notifications->markAllRead($request->user());

        return $this->success(['updated' => $updated], 'All notifications marked as read.');
    }

    public function preferences(Request $request): JsonResponse
    {
        return $this->success(new NotificationPreferenceResource($this->notifications->preferences($request->user())));
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        return $this->success(
            new NotificationPreferenceResource($this->notifications->updatePreferences($request->user(), $request->validated())),
            'Notification preferences updated.'
        );
    }

    public function storePushToken(StorePushTokenRequest $request): JsonResponse
    {
        $token = $this->notifications->storePushToken($request->user(), $request->validated());

        return $this->success([
            'id' => $token->id,
            'platform' => $token->platform,
            'device_id' => $token->device_id,
            'last_used_at' => optional($token->last_used_at)->toISOString(),
        ], 'Push token registered.', 201);
    }

    public function deletePushToken(Request $request, int $token): JsonResponse
    {
        $this->notifications->deletePushToken($request->user(), $token);

        return $this->success(message: 'Push token removed.');
    }
}
