<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HelpChat;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Api\V1\HelpChat\HelpChatMessageResource;
use App\Http\Resources\Api\V1\HelpChat\HelpChatThreadResource;
use App\Services\HelpChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Member side of the Help Center, under `/api/v1/help-chat`.
 *
 * Every route is Sanctum-authenticated; the member only ever touches their
 * own thread because the service resolves it from `$request->user()`.
 */
class HelpChatController extends ApiController
{
    public function __construct(private readonly HelpChatService $help)
    {
    }

    /**
     * GET /help-chat/thread — the member's conversation (created on first use).
     */
    public function thread(Request $request): JsonResponse
    {
        return $this->success(new HelpChatThreadResource($this->help->threadFor($request->user())));
    }

    /**
     * GET /help-chat/messages — paginated messages of the member's thread.
     */
    public function messages(Request $request): JsonResponse
    {
        $messages = $this->help->messagesFor($request->user(), (int) $request->query('per_page', '50'));

        return HelpChatMessageResource::collection($messages)->additional(['success' => true])->response();
    }

    /**
     * POST /help-chat/messages — the member reports their issue / replies.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required_without:attachments', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $message = $this->help->sendFromUser(
            $request->user(),
            (string) ($validated['message'] ?? ''),
            $request->file('attachments', [])
        );

        return $this->success(new HelpChatMessageResource($message), 'Message sent successfully.', 201);
    }
}
