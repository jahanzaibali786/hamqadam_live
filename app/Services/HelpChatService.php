<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\HelpChatMessageSent;
use App\Models\HelpChatMessage;
use App\Models\HelpChatThread;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The Help Center conversation: member side (the app) and admin side (the
 * panel) meet here, so both speak exactly the same shape and the unread
 * bookkeeping cannot drift.
 *
 * One thread per member, created lazily on the first message — the member
 * never presses "new conversation", the admin never sees an empty thread.
 */
class HelpChatService
{
    // ── Member side (the app) ───────────────────────────────────────────────

    /**
     * The member's conversation, created on first use.
     */
    public function threadFor(User $user): HelpChatThread
    {
        $thread = HelpChatThread::with('user')->firstOrCreate(
            ['user_id' => $user->id],
            ['status' => HelpChatThread::STATUS_OPEN],
        );

        // Opening the conversation is reading it: anything waiting is seen.
        $this->markReadForMember($thread);

        return $thread->fresh(['user']);
    }

    /**
     * Messages of the member's thread, newest first (the app renders a
     * reversed list, same as the member-to-member chat).
     */
    public function messagesFor(User $user, int $perPage = 50): LengthAwarePaginator
    {
        $thread = $this->threadFor($user);

        return HelpChatMessage::with('sender')
            ->where('thread_id', $thread->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * The member sends one message.
     */
    public function sendFromUser(User $user, string $message, array $files = []): HelpChatMessage
    {
        $thread = $this->threadFor($user);
        $this->assertThreadOpen($thread);

        return $this->storeMessage($thread, $user, $message, $files, false);
    }

    /**
     * Marks every admin message in the member's thread as seen.
     */
    public function markReadForMember(HelpChatThread $thread): void
    {
        $updated = HelpChatMessage::where('thread_id', $thread->id)
            ->where('sender_user_id', '!=', $thread->user_id)
            ->where('seen', 0)
            ->update(['seen' => 1, 'read_at' => now()]);

        if ($updated > 0 && (int) $thread->user_unread_count > 0) {
            $thread->forceFill(['user_unread_count' => 0])->save();
        }
    }

    // ── Admin side (the panel) ──────────────────────────────────────────────

    /**
     * One row per member with a conversation, newest activity first.
     *
     * `q` filters by the member's name, code or email — the panel search box.
     * `status` selects open / closed / all.
     */
    public function adminThreads(string $status = 'all', string $q = '', int $perPage = 20): LengthAwarePaginator
    {
        $query = HelpChatThread::with(['user', 'lastMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at');

        if (in_array($status, [HelpChatThread::STATUS_OPEN, HelpChatThread::STATUS_CLOSED], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
            $query->whereHas('user', static function ($userQuery) use ($like) {
                $userQuery->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhereRaw('CONCAT(first_name, " ", last_name) like ?', [$like])
                    ->orWhere('code', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * One thread for the admin, with the member attached.
     */
    public function adminThread(int $threadId): HelpChatThread
    {
        $thread = HelpChatThread::with(['user', 'lastMessage'])->find($threadId);

        if (! $thread) {
            abort(404, 'Help chat thread not found.');
        }

        return $thread;
    }

    /**
     * Messages of one thread for the admin panel, oldest first — the panel
     * renders a normal top-to-bottom conversation.
     */
    public function adminMessages(HelpChatThread $thread, int $perPage = 100): LengthAwarePaginator
    {
        return HelpChatMessage::with('sender')
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    /**
     * The admin replies from the panel.
     */
    public function sendFromAdmin(User $admin, HelpChatThread $thread, string $message, array $files = []): HelpChatMessage
    {
        return $this->storeMessage($thread, $admin, $message, $files, true);
    }

    /**
     * The newest message of a thread, sender loaded — what the admin panel's
     * reply POST renders into HTML so the page can append it without a reload.
     */
    public function lastMessageOf(HelpChatThread $thread): HelpChatMessage
    {
        return HelpChatMessage::with('sender')
            ->where('thread_id', $thread->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * Marks the member's messages as seen — the admin has the conversation open.
     */
    public function markReadForAdmin(HelpChatThread $thread): void
    {
        $updated = HelpChatMessage::where('thread_id', $thread->id)
            ->where('sender_user_id', $thread->user_id)
            ->where('seen', 0)
            ->update(['seen' => 1, 'read_at' => now()]);

        if ($updated > 0 && (int) $thread->admin_unread_count > 0) {
            $thread->forceFill(['admin_unread_count' => 0])->save();
        }
    }

    /**
     * Close / reopen a conversation. A closed thread stops the member from
     * sending anything further, which is how an admin ends a resolved issue.
     */
    public function setStatus(HelpChatThread $thread, string $status): HelpChatThread
    {
        if (! in_array($status, [HelpChatThread::STATUS_OPEN, HelpChatThread::STATUS_CLOSED], true)) {
            abort(422, 'Unknown status.');
        }

        $thread->forceFill(['status' => $status])->save();

        return $thread->fresh(['user']);
    }

    /**
     * Removes a thread and everything in it. Irreversible.
     */
    public function deleteThread(HelpChatThread $thread): void
    {
        $thread->messages()->delete();
        $thread->delete();
    }

    // ── Shared plumbing ─────────────────────────────────────────────────────

    /**
     * The one place a message is written, whichever side sent it.
     *
     * Everything else in this class is thin wrappers so each side's controller
     * reads cleanly; the unread bookkeeping and the broadcast live here, once.
     */
    private function storeMessage(
        HelpChatThread $thread,
        User $sender,
        string $message,
        array $files,
        bool $fromAdmin
    ): HelpChatMessage {
        $text = trim($message);
        $attachmentIds = $this->storeAttachments($files);

        if ($text === '' && $attachmentIds === []) {
            abort(422, 'Message cannot be empty.');
        }

        return DB::transaction(function () use ($thread, $sender, $text, $attachmentIds, $fromAdmin) {
            $message = HelpChatMessage::create([
                'thread_id' => $thread->id,
                'sender_user_id' => $sender->id,
                'message' => $text,
                'message_type' => $attachmentIds !== [] ? 'file' : 'text',
                'attachment' => $attachmentIds !== [] ? implode(',', $attachmentIds) : null,
                'seen' => 0,
            ]);

            // Bump the recipient's unread counter and the thread's activity.
            $thread->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
                'admin_unread_count' => $fromAdmin ? $thread->admin_unread_count : $thread->admin_unread_count + 1,
                'user_unread_count' => $fromAdmin ? $thread->user_unread_count + 1 : $thread->user_unread_count,
                'status' => $fromAdmin && $thread->isClosed() ? HelpChatThread::STATUS_OPEN : $thread->status,
            ])->save();

            // Realtime to both channels; never let a broken socket fail the POST.
            $this->broadcastSafely(new HelpChatMessageSent(
                $message,
                (int) $thread->id,
                (int) $thread->user_id,
                $fromAdmin
            ));

            // Wake the member's phone when the reply came from the panel.
            if ($fromAdmin) {
                $this->pushToMember($thread, $message);
            }

            return $message;
        });
    }

    /**
     * Files come in as multipart uploads and are stored through the same
     * `uploads` table every other attachment in the codebase uses.
     *
     * @return list<int>
     */
    private function storeAttachments(array $files): array
    {
        $ids = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $ids[] = (int) upload_api_file($file);
            }
        }

        return $ids;
    }

    private function assertThreadOpen(HelpChatThread $thread): void
    {
        if ($thread->isClosed()) {
            abort(403, 'This conversation has been closed by support. Please start a new one from the Help Center.');
        }
    }

    /**
     * `broadcast()` never throws on a missing Pusher server in this codebase's
     * configuration, but a misconfigured broadcast driver can — and a member
     * whose message just saved must not see a 500 because of it.
     */
    private function broadcastSafely(object $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $throwable) {
            Log::warning('Help chat broadcast failed.', [
                'event' => $event::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * FCM push so the member's phone wakes even with the app closed.
     *
     * `type: help_chat` is what the app's notification router keys on —
     * see NotificationService._handleNotificationPayload in the Flutter app.
     */
    private function pushToMember(HelpChatThread $thread, HelpChatMessage $message): void
    {
        try {
            $member = User::find($thread->user_id);
            if (! $member) {
                return;
            }

            $body = (string) ($message->message ?? '');
            if ($body === '' && $message->attachment) {
                $body = '📎 Attachment';
            }

            FcmV1Service::sendToUser(
                (int) $member->id,
                [
                    'title' => 'HamQadam Help Center',
                    'body' => mb_substr($body !== '' ? $body : 'You have a new reply from support.', 0, 200),
                ],
                [
                    'type' => 'help_chat',
                    'thread_id' => (string) $thread->id,
                    'message_id' => (string) $message->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Help chat FCM push failed.', ['error' => $e->getMessage()]);
        }
    }
}
