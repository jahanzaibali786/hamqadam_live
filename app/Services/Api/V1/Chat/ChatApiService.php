<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Chat;

use App\Enums\ApiErrorCode;
use App\Enums\ChatMessageType;
use App\Exceptions\ApiException;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\ChatTypingIndicator;
use App\Models\ReportedUser;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ChatApiService
{
    public function threads(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return ChatThread::with(['sender', 'receiver', 'chats' => fn ($query) => $query->latest()->limit(1)])
            ->where(function ($query) use ($user) {
                $query->where('sender_user_id', $user->id)
                    ->orWhere('receiver_user_id', $user->id);
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    public function messages(User $user, int $threadId, int $perPage = 20): LengthAwarePaginator
    {
        $thread = $this->threadForUser($user, $threadId);
        $this->markRead($user, $thread);

        return Chat::with(['sender', 'replyTo.sender'])
            ->where('chat_thread_id', $thread->id)
            ->whereNull($this->deleteColumnFor($thread, $user))
            ->latest()
            ->paginate($perPage);
    }

    public function send(User $user, int $threadId, array $data, array $files = []): Chat
    {
        $thread = $this->threadForUser($user, $threadId);
        $this->ensureNotBlocked($thread);

        if (! empty($data['reply_to_chat_id'])) {
            $this->ensureReplyBelongsToThread((int) $data['reply_to_chat_id'], $thread);
        }

        $attachments = array_map(fn (UploadedFile $file) => upload_api_file($file), $files);

        return DB::transaction(function () use ($thread, $user, $data, $attachments) {
            $message = Chat::create([
                'chat_thread_id' => $thread->id,
                'sender_user_id' => $user->id,
                'message' => $this->maskSensitiveText((string) ($data['message'] ?? '')),
                'message_type' => $data['message_type'] ?? $this->detectType($attachments),
                'reply_to_chat_id' => $data['reply_to_chat_id'] ?? null,
                'attachment' => $attachments !== [] ? implode(',', $attachments) : null,
                'seen' => 0,
                'delivered_at' => now(),
                'moderation_status' => 'clean',
                'toxicity_score' => 0,
            ]);

            $thread->forceFill(['last_message_at' => now()])->save();

            return $message->load(['sender', 'replyTo.sender']);
        });
    }

    public function typing(User $user, int $threadId): void
    {
        $thread = $this->threadForUser($user, $threadId);

        ChatTypingIndicator::updateOrCreate([
            'chat_thread_id' => $thread->id,
            'user_id' => $user->id,
        ], [
            'expires_at' => now()->addSeconds(10),
        ]);
    }

    public function markRead(User $user, ChatThread $thread): void
    {
        Chat::where('chat_thread_id', $thread->id)
            ->where('sender_user_id', '!=', $user->id)
            ->where('seen', 0)
            ->update([
                'seen' => 1,
                'read_at' => now(),
            ]);
    }

    public function deleteMessageForMe(User $user, int $messageId): void
    {
        $message = Chat::with('chatThread')->find($messageId);
        if (! $message || ! $message->chatThread || ! $this->isParticipant($message->chatThread, $user)) {
            throw new ApiException('Message not found.', 404, ApiErrorCode::NotFound->value);
        }

        $message->forceFill([
            $this->deleteColumnFor($message->chatThread, $user) => now(),
        ])->save();
    }

    public function block(User $user, int $threadId): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        $thread->forceFill(['blocked_by_user' => $user->id])->save();

        return $thread->fresh(['sender', 'receiver']);
    }

    public function unblock(User $user, int $threadId): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        if ((int) $thread->blocked_by_user !== (int) $user->id) {
            throw new ApiException('Only the user who blocked this thread can unblock it.', 403, ApiErrorCode::Forbidden->value);
        }

        $thread->forceFill(['blocked_by_user' => null])->save();

        return $thread->fresh(['sender', 'receiver']);
    }

    public function report(User $user, int $threadId, string $reason): void
    {
        $thread = $this->threadForUser($user, $threadId);
        $reportedUserId = (int) $thread->sender_user_id === (int) $user->id ? $thread->receiver_user_id : $thread->sender_user_id;

        ReportedUser::firstOrCreate([
            'user_id' => $reportedUserId,
            'reported_by' => $user->id,
        ], [
            'reason' => $reason,
        ]);
    }

    private function threadForUser(User $user, int $threadId): ChatThread
    {
        $thread = ChatThread::with(['sender', 'receiver'])->find($threadId);

        if (! $thread || ! $this->isParticipant($thread, $user)) {
            throw new ApiException('Chat thread not found.', 404, ApiErrorCode::NotFound->value);
        }

        return $thread;
    }

    private function isParticipant(ChatThread $thread, User $user): bool
    {
        return in_array((int) $user->id, [(int) $thread->sender_user_id, (int) $thread->receiver_user_id], true);
    }

    private function ensureNotBlocked(ChatThread $thread): void
    {
        if ($thread->blocked_by_user) {
            throw new ApiException('This chat thread is blocked.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function ensureReplyBelongsToThread(int $messageId, ChatThread $thread): void
    {
        if (! Chat::whereKey($messageId)->where('chat_thread_id', $thread->id)->exists()) {
            throw new ApiException('Reply message does not belong to this thread.', 422, ApiErrorCode::ValidationFailed->value);
        }
    }

    private function deleteColumnFor(ChatThread $thread, User $user): string
    {
        return (int) $thread->sender_user_id === (int) $user->id ? 'deleted_by_sender_at' : 'deleted_by_receiver_at';
    }

    private function detectType(array $attachments): string
    {
        return $attachments === [] ? ChatMessageType::Text->value : ChatMessageType::Mixed->value;
    }

    private function maskSensitiveText(string $message): string
    {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email hidden]', $message) ?? $message;

        return preg_replace('/(?<!\d)(?:\+?\d[\d\s().-]{7,}\d)(?!\d)/', '[phone hidden]', $message) ?? $message;
    }
}
