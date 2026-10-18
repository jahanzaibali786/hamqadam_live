<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\SupportTicket\SupportTicketCategoryResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\SupportCategory;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Legacy mobile endpoints; ownership rules must match the website. */
class SupportTicketController extends Controller
{
    public function index()
    {
        return $this->my_ticket();
    }

    public function my_ticket()
    {
        abort_unless(addon_activation('support_tickets'), 403);
        return SupportTicketResource::collection(
            SupportTicket::where('sender_user_id', auth()->id())
                ->latest()->paginate(10)
        )->additional(['result' => true]);
    }

    public function store(Request $request)
    {
        abort_unless(addon_activation('support_tickets'), 403);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'support_category_id' => ['nullable','integer','exists:support_categories,id'],
            'attachment' => ['nullable','file','max:5120'],
        ]);
        $attachment = $request->hasFile('attachment') ? upload_api_file($request->file('attachment')) : null;
        $ticket = SupportTicket::create([
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'support_category_id' => $data['support_category_id'] ?? null,
            'sender_user_id' => $request->user()->id,
            'assigned_user_id' => get_setting('default_ticket_assigned_user') ?: null,
            'ticket_id' => now()->format('YmdHis') . '-' . random_int(1000, 9999),
            'status' => 0,
            'attachments' => $attachment,
            'expected_response_at' => now()->addHours(max(1, min(168, (int) get_setting('support_first_response_hours', 24)))),
        ]);
        return $this->response_data($ticket->ticket_id);
    }

    public function show($id)
    {
        abort_unless(addon_activation('support_tickets'), 403);
        $ticket = SupportTicket::where('sender_user_id', auth()->id())->findOrFail($id);
        $ticket->forceFill(['seen' => 1])->save();
        SupportTicketReply::where('support_ticket_id', $ticket->id)
            ->where('replied_user_id', '!=', auth()->id())->update(['seen' => 1]);
        return new SupportTicketResource($ticket);
    }

    public function update(Request $request, $id)
    {
        abort(405, 'Tickets are updated through replies and support agents.');
    }

    public function ticket_reply(Request $request)
    {
        abort_unless(addon_activation('support_tickets'), 403);
        $data = $request->validate([
            'support_ticket_id' => ['required','integer','exists:support_tickets,id'],
            'reply' => ['required','string','max:10000'],
            'attachment' => ['nullable','file','max:5120'],
        ]);
        $ticket = SupportTicket::whereKey((int)$data['support_ticket_id'])
            ->where('sender_user_id', auth()->id())->firstOrFail();
        if ((string) $ticket->status === '1') {
            throw ValidationException::withMessages(['reply' => 'This ticket is closed. Open a new ticket if you need further assistance.']);
        }
        $attachment = $request->hasFile('attachment') ? upload_api_file($request->file('attachment')) : null;
        DB::transaction(function () use ($ticket, $data, $attachment) {
            $current = SupportTicket::whereKey($ticket->id)->where('sender_user_id', auth()->id())
                ->lockForUpdate()->firstOrFail();
            if ((string) $current->status === '1') {
                throw ValidationException::withMessages(['reply' => 'This ticket has already been closed.']);
            }
            SupportTicketReply::create([
                'support_ticket_id' => $current->id,
                'replied_user_id' => auth()->id(),
                'reply' => $data['reply'],
                'attachments' => $attachment,
            ]);
            // Member replies must never set an arbitrary admin status.
        });
        return $this->success_message('Reply has been sent successfully');
    }

    public function destroy($id)
    {
        abort(405, 'Ticket deletion is not available from the mobile API.');
    }

    public function support_ticket_categories()
    {
        return SupportTicketCategoryResource::collection(SupportCategory::latest()->paginate(10));
    }

    public function rate(Request $request, $id)
    {
        abort_unless(addon_activation('support_tickets'), 403);
        $data = $request->validate([
            'rating' => ['required','integer','between:1,5'],
            'comment' => ['nullable','string','max:1000'],
        ]);
        DB::transaction(function () use ($id, $data) {
            $ticket = SupportTicket::whereKey($id)->where('sender_user_id', auth()->id())->lockForUpdate()->firstOrFail();
            if ((string)$ticket->status !== '1' || $ticket->rated_at !== null) {
                throw ValidationException::withMessages(['rating' => 'Only closed, unrated tickets can be rated.']);
            }
            $ticket->update(['rating' => $data['rating'], 'rating_comment' => $data['comment'] ?? null, 'rated_at' => now()]);
        });
        return $this->success_message('Thank you for rating our support.');
    }
}
