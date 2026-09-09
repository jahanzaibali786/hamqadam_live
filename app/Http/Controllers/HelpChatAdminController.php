<?php

namespace App\Http\Controllers;

use App\Models\HelpChatMessage;
use App\Models\HelpChatThread;
use App\Services\HelpChatService;
use Illuminate\Http\Request;

/**
 * Admin panel section for the Help Center chat.
 *
 * The list is one row per member with a conversation, so the admin sees who
 * needs help at a glance; opening a row loads the whole conversation and the
 * reply box. Replies are pushed to the member live over the same event the
 * app listens on, so no refresh is needed on either side.
 */
class HelpChatAdminController extends Controller
{
    public function __construct(private readonly HelpChatService $help)
    {
    }

    /**
     * GET /admin/help-chat — the list, filterable by status and search.
     */
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $threads = $this->help->adminThreads($status, $q);

        $openCount = HelpChatThread::where('status', HelpChatThread::STATUS_OPEN)->count();
        $closedCount = HelpChatThread::where('status', HelpChatThread::STATUS_CLOSED)->count();
        $totalUnread = (int) HelpChatThread::query()->sum('admin_unread_count');

        return view('admin.help_chat.index', compact('threads', 'status', 'q', 'openCount', 'closedCount', 'totalUnread'));
    }

    /**
     * GET /admin/help-chat/{thread} — one conversation.
     */
    public function show(int $id)
    {
        $thread = $this->help->adminThread($id);
        $messages = $this->help->adminMessages($thread);

        // Having the conversation open is reading it.
        $this->help->markReadForAdmin($thread);

        return view('admin.help_chat.show', compact('thread', 'messages'));
    }

    /**
     * POST /admin/help-chat/{thread}/reply — the admin's reply.
     */
    public function reply(Request $request, int $id)
    {
        $data = $request->validate([
            'message' => ['required_without:attachments', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
            'status' => ['nullable', 'in:open,closed'],
        ]);

        $thread = $this->help->adminThread($id);
        $this->help->sendFromAdmin(
            $request->user(),
            $thread,
            (string) ($data['message'] ?? ''),
            $request->file('attachments', [])
        );

        if (! empty($data['status']) && $data['status'] !== $thread->status) {
            $this->help->setStatus($thread, $data['status']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply sent.',
                // The rendered bubble, so the page's fetch can append it
                // without a reload and the panel stays on the conversation.
                'message_html' => view('admin.help_chat.partials.message', [
                    'message' => $this->help->lastMessageOf($thread),
                ])->render(),
            ]);
        }

        return redirect()
            ->route('admin.help-chat.show', $thread->id)
            ->with('success', translate('Reply sent to the member.'));
    }

    /**
     * GET /admin/help-chat/{thread}/message-html — one member message,
     * rendered. The Pusher handler in show.blade.php fetches this when a
     * broadcast lands, so what appears matches a page reload exactly.
     */
    public function messageHtml(Request $request, int $id)
    {
        $thread = $this->help->adminThread($id);

        $messageId = (int) $request->query('message_id', '0');
        $message = $messageId > 0
            ? HelpChatMessage::with('sender')->where('thread_id', $thread->id)->find($messageId)
            : null;

        if (! $message) {
            return response()->json(['success' => false], 404);
        }

        // Reading it here, from the panel, is what clears the badge.
        $this->help->markReadForAdmin($thread);

        return response()->json([
            'success' => true,
            'message_html' => view('admin.help_chat.partials.message', ['message' => $message])->render(),
        ]);
    }

    /**
     * POST /admin/help-chat/{thread}/status — close or reopen.
     */
    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,closed'],
        ]);

        $thread = $this->help->setStatus($this->help->adminThread($id), $data['status']);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'status' => $thread->status]);
        }

        return redirect()
            ->route('admin.help-chat.show', $thread->id)
            ->with('success', $thread->status === HelpChatThread::STATUS_CLOSED
                ? translate('Conversation closed.')
                : translate('Conversation reopened.'));
    }

    /**
     * GET /admin/help-chat/{thread}/destroy — delete the whole conversation.
     */
    public function destroy(int $id)
    {
        $thread = $this->help->adminThread($id);
        $this->help->deleteThread($thread);

        return redirect()
            ->route('admin.help-chat.index')
            ->with('success', translate('Conversation deleted.'));
    }
}
