<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Chat\ChatListRequest;
use App\Http\Requests\Api\V1\Chat\ReportChatRequest;
use App\Http\Requests\Api\V1\Chat\SendMessageRequest;
use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use App\Http\Resources\Api\V1\Chat\ChatThreadResource;
use App\Services\Api\V1\Chat\ChatApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends ApiController
{
    public function __construct(private readonly ChatApiService $chat)
    {
    }

    public function threads(ChatListRequest $request): JsonResponse
    {
        return ChatThreadResource::collection(
            $this->chat->threads($request->user(), (int) ($request->validated('per_page') ?? 20))
        )->additional(['success' => true])->response();
    }

    public function messages(ChatListRequest $request, int $thread): JsonResponse
    {
        return ChatMessageResource::collection(
            $this->chat->messages($request->user(), $thread, (int) ($request->validated('per_page') ?? 20))
        )->additional(['success' => true])->response();
    }

    public function send(SendMessageRequest $request, int $thread): JsonResponse
    {
        $message = $this->chat->send(
            $request->user(),
            $thread,
            $request->validated(),
            $request->file('attachments', [])
        );

        return $this->success(new ChatMessageResource($message), 'Message sent successfully.', 201);
    }

    /**
     * POST /chat/threads/{thread}/delivered — the recipient's app acknowledges
     * having the messages on device, turning the sender's single tick into a
     * double tick.
     */
    public function delivered(Request $request, int $thread): JsonResponse
    {
        $this->chat->markDelivered($request->user(), $thread);

        return $this->success(message: 'Messages marked as delivered.');
    }

    public function typing(Request $request, int $thread): JsonResponse
    {
        $this->chat->typing($request->user(), $thread);

        return $this->success(message: 'Typing indicator updated.');
    }

    /**
     * Sets the thread's disappearing-message TTL (seconds; 0 = off).
     * Afterwards every new message on BOTH clients inherits this default.
     */
    public function setDisappear(Request $request, int $thread): JsonResponse
    {
        $validated = $request->validate([
            'disappear_after' => ['required', 'integer', 'min:0', 'max:31536000'],
        ]);

        $value = $this->chat->setDisappearAfter($request->user(), $thread, (int) $validated['disappear_after']);

        return $this->success(
            data: ['disappear_after' => $value],
            message: $value > 0
                ? 'New messages will disappear after ' . now()->addSeconds($value)->diffForHumans(now(), ['parts' => 1]) . '.'
                : 'Disappearing messages turned off.'
        );
    }

    public function deleteForMe(Request $request, int $message): JsonResponse
    {
        $this->chat->deleteMessageForMe($request->user(), $message);

        return $this->success(message: 'Message deleted for you.');
    }

    public function clear(Request $request, int $thread): JsonResponse
    {
        return $this->success(new ChatThreadResource($this->chat->clear($request->user(), $thread)), 'Chat cleared successfully.');
    }

    public function block(Request $request, int $thread): JsonResponse
    {
        return $this->success(new ChatThreadResource($this->chat->block($request->user(), $thread)), 'Chat blocked successfully.');
    }

    public function unblock(Request $request, int $thread): JsonResponse
    {
        return $this->success(new ChatThreadResource($this->chat->unblock($request->user(), $thread)), 'Chat unblocked successfully.');
    }

    public function report(ReportChatRequest $request, int $thread): JsonResponse
    {
        return $this->success(new ChatThreadResource($this->chat->report($request->user(), $thread, $request->validated('reason'))), 'Chat reported successfully.');
    }
}
