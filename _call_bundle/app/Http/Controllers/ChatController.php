<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Events\ChatMessageRead;
use App\Events\ChatMessageSent;
use App\Models\Chat;
use App\Models\ChatThread;
use App\Models\Call;
use App\Models\ReportedUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $chat_threads = ChatThread::where('sender_user_id', Auth::user()->id)
            ->orWhere('receiver_user_id', Auth::user()->id)
            ->get();
        return view('frontend.member.messages.index', compact('chat_threads'));
    }
    public function chat_view($id)
    {
        $chat_thread = ChatThread::findOrFail($id);
        $this->ensureParticipant($chat_thread);
        $readIds = [];
        foreach ($chat_thread->chats as $chat) {
            if ($chat->sender_user_id != Auth::user()->id && ! $chat->seen) {
                $chat->seen = 1;
                $chat->read_at = now();
                $chat->save();
                $readIds[] = $chat->id;
            }
        }
        if ($readIds !== []) {
            $this->broadcastSafely(new ChatMessageRead($chat_thread->id, $readIds, Auth::id(), now()->toISOString()));
        }
        $chats = $this->visibleChats($chat_thread)->latest()->limit(20)->with('sender', 'replyTo.sender')->get();
        $chat_partner = $this->chatPartner($chat_thread);
        $chat_is_blocked = $this->isBlocked($chat_thread);
        $chat_blocked_by_me = (int) $chat_thread->blocked_by_user === (int) Auth::id();
        $chat_blocked_by_other = ! empty($chat_thread->blocked_by_user) && ! $chat_blocked_by_me;
        $chat_blocker_name = $chat_thread->blocked_by ? trim(($chat_thread->blocked_by->first_name ?? '') . ' ' . ($chat_thread->blocked_by->last_name ?? '')) : null;
        $can_send_message = ! $chat_is_blocked;
        $call_logs = Call::with(['caller', 'receiver'])
            ->where('conversation_id', $chat_thread->id)
            ->latest()
            ->limit(20)
            ->get();
        $user_to_show = $this->userToShow($chat_thread);
        return view('frontend.member.messages.messages', compact('chats', 'chat_thread', 'chat_partner', 'chat_is_blocked', 'chat_blocked_by_me', 'chat_blocked_by_other', 'chat_blocker_name', 'can_send_message', 'user_to_show', 'call_logs'));
    }
    public function get_old_messages(Request $request)
    {
        $chat = Chat::findOrFail($request->first_message_id);
        $chat_thread = ChatThread::findOrFail($chat->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        $chats = $this->visibleChats($chat_thread)
            ->where('id', '<', $chat->id)
            ->latest()
            ->limit(20)
            ->with('sender', 'replyTo.sender')
            ->get();
        if (count($chats) > 0) {
            return [
                'messages' => view('frontend.member.messages.messages_part', compact('chats'))->render(),
                'first_message_id' => $chats->last()->id,
            ];
        }
        return [
            'messages' => '',
            'first_message_id' => 0,
        ];
    }
    public function chat_refresh($id)
    {
        $chat_thread = ChatThread::findOrFail($id);
        $this->ensureParticipant($chat_thread);
        $chats = $this->visibleChats($chat_thread)
            ->where('sender_user_id', '!=', Auth::user()->id)
            ->where('seen', 0)
            ->with('sender', 'replyTo.sender')
            ->get();
        $readIds = [];
        foreach ($chats as $value) {
            $value->seen = 1;
            $value->read_at = now();
            $value->save();
            $readIds[] = $value->id;
        }
        if ($readIds !== []) {
            $this->broadcastSafely(new ChatMessageRead($chat_thread->id, $readIds, Auth::id(), now()->toISOString()));
        }
        return [
            'messages' => view('frontend.member.messages.messages_left_single', compact('chats'))->render(),
            'count' => count($chats),
        ];
    }
    public function chat_reply(Request $request)
    {
        $chat_thread = ChatThread::findOrFail((int) $request->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        if ($this->isBlocked($chat_thread)) {
            return response()->json([
                'message' => translate('This chat is blocked. Unblock it before sending messages.'),
            ], 403);
        }
        $chat = new Chat();
        $chat->chat_thread_id = $chat_thread->id;
        $chat->sender_user_id = Auth::user()->id;
        $chat->message = $request->message;
        if ($request->attachment != null) {
            $chat->attachment = json_encode(explode(',', $request->attachment));
        }
        $chat->save();
        $recipientId = (int) $chat_thread->sender_user_id === (int) Auth::id()
            ? (int) $chat_thread->receiver_user_id
            : (int) $chat_thread->sender_user_id;
        $this->broadcastSafely(new ChatMessageSent($chat->load('sender', 'replyTo.sender'), Auth::user(), (int) $chat->chat_thread_id, $recipientId));
        return view('frontend.member.messages.messages_right_single', compact('chat'));
    }
    public function unread_count(Request $request)
    {
        $thread_ids = ChatThread::where('sender_user_id', Auth::user()->id)
            ->orWhere('receiver_user_id', Auth::user()->id)
            ->pluck('id')
            ->toArray();
        $activeThreadId = (int) $request->input('active_thread_id', 0);
        $baseQuery = Chat::whereIn('chat_thread_id', $thread_ids)
            ->where('sender_user_id', '!=', Auth::user()->id)
            ->where('seen', 0);
        if ($activeThreadId > 0) {
            $baseQuery->where('chat_thread_id', '!=', $activeThreadId);
        }
        $total = (clone $baseQuery)->count();
        $latest = (clone $baseQuery)->latest()->first();
        $sender_name = '';
        $message_preview = '';
        if ($latest) {
            $sender = $latest->sender;
            if ($sender) {
                $sender_name = trim($sender->first_name . ' ' . $sender->last_name);
            }
            $message_preview = $latest->message ?? 'Attachment';
        }
        return response()->json([
            'count' => $total,
            'sender_name' => $sender_name,
            'message' => $message_preview,
        ]);
    }
    public function block_chat(Request $request): JsonResponse
    {
        $chat_thread = ChatThread::findOrFail((int) $request->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        if ((int) $chat_thread->blocked_by_user > 0 && (int) $chat_thread->blocked_by_user !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => translate('This chat has already been blocked by the other user.'),
            ], 422);
        }
        $chat_thread->active = 0;
        $chat_thread->blocked_by_user = Auth::id();
        $chat_thread->save();
        return response()->json([
            'success' => true,
            'message' => translate('Chat blocked successfully.'),
            'status' => 'blocked',
        ]);
    }
    public function unblock_chat(Request $request): JsonResponse
    {
        $chat_thread = ChatThread::findOrFail((int) $request->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        if ((int) $chat_thread->blocked_by_user !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => translate('Only the user who blocked this chat can unblock it.'),
            ], 422);
        }
        $chat_thread->active = 1;
        $chat_thread->blocked_by_user = null;
        $chat_thread->save();
        return response()->json([
            'success' => true,
            'message' => translate('Chat unblocked successfully.'),
            'status' => 'active',
        ]);
    }
    public function clear_chat(Request $request): JsonResponse
    {
        $chat_thread = ChatThread::findOrFail((int) $request->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        $deleteColumn = $this->deleteColumnFor($chat_thread, Auth::user());
        $updated = Chat::where('chat_thread_id', $chat_thread->id)
            ->whereNull($deleteColumn)
            ->update([
                $deleteColumn => now(),
            ]);
        return response()->json([
            'success' => true,
            'message' => translate('Chat cleared for your side successfully.'),
            'count' => $updated,
        ]);
    }
    public function delete_message(Request $request): JsonResponse
    {
        $chat = Chat::findOrFail((int) $request->message_id);
        $chat_thread = ChatThread::findOrFail((int) $chat->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        $deleteColumn = $this->deleteColumnFor($chat_thread, Auth::user());
        $chat->{$deleteColumn} = now();
        $chat->save();
        return response()->json([
            'success' => true,
            'message' => translate('Message deleted for your side successfully.'),
        ]);
    }
    public function report_chat(Request $request): JsonResponse
    {
        $chat_thread = ChatThread::findOrFail((int) $request->chat_thread_id);
        $this->ensureParticipant($chat_thread);
        $reportedUserId = (int) $chat_thread->sender_user_id === (int) Auth::id()
            ? (int) $chat_thread->receiver_user_id
            : (int) $chat_thread->sender_user_id;
        ReportedUser::create([
            'user_id' => $reportedUserId,
            'reported_by' => Auth::id(),
            'reason' => $request->reason ?? translate('Reported from chat.'),
            'source' => 'chat',
            'chat_thread_id' => $chat_thread->id,
        ]);
        $chat_thread->active = 0;
        $chat_thread->blocked_by_user = Auth::id();
        $chat_thread->save();
        return response()->json([
            'success' => true,
            'message' => translate('Chat reported successfully.'),
            'status' => 'blocked',
        ]);
    }
    public function interview_status(Request $request)
    {
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);
        if ($chat_thread->interview == 1) {
            $chat_thread->interview = 0;
        } else {
            $chat_thread->interview = 1;
        }
        return $chat_thread->save() ? 1 : 0;
    }
    public function block_status(Request $request)
    {
        $chat_thread = ChatThread::findOrFail($request->chat_thread_id);
        if ($chat_thread->active == 1) {
            $chat_thread->active = 0;
        } else {
            $chat_thread->active = 1;
        }
        return $chat_thread->save() ? 1 : 0;
    }
    private function visibleChats(ChatThread $chat_thread)
    {
        return $chat_thread->chats()
            ->whereNull($this->deleteColumnFor($chat_thread, Auth::user()))
            ->with('sender', 'replyTo.sender');
    }
    private function deleteColumnFor(ChatThread $thread, User $user): string
    {
        return (int) $thread->sender_user_id === (int) $user->id ? 'deleted_by_sender_at' : 'deleted_by_receiver_at';
    }
    private function ensureParticipant(ChatThread $chat_thread): void
    {
        if (! in_array((int) Auth::id(), [(int) $chat_thread->sender_user_id, (int) $chat_thread->receiver_user_id], true)) {
            abort(403);
        }
    }
    private function chatPartner(ChatThread $chat_thread): ?User
    {
        if ((int) $chat_thread->sender_user_id === (int) Auth::id()) {
            return $chat_thread->receiver;
        }
        return $chat_thread->sender;
    }
    private function userToShow(ChatThread $chat_thread): string
    {
        return (int) Auth::id() === (int) $chat_thread->sender_user_id ? 'receiver' : 'sender';
    }
    private function isBlocked(ChatThread $chat_thread): bool
    {
        return (int) $chat_thread->active !== 1 || ! empty($chat_thread->blocked_by_user);
    }
    private function broadcastSafely(object $event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (Throwable $throwable) {
            Log::warning('Realtime chat broadcast failed.', [
                'event' => $event::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}