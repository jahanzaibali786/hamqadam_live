<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportTicketController extends Controller
{
    public function index()
    {
        return response()->json(SupportTicket::latest()->paginate(20));
    }

    public function my_ticket()
    {
        return response()->json(SupportTicket::where('assigned_user_id', auth()->id())->latest()->paginate(20));
    }

    public function solved_ticket()
    {
        return response()->json(SupportTicket::where('status', 1)->latest()->paginate(20));
    }

    public function active_ticket()
    {
        return response()->json(SupportTicket::where('status', 0)->latest()->paginate(20));
    }

    public function user_ticket_create()
    {
        return redirect()->route('client.scope.support');
    }

    public function user_index()
    {
        return response()->json(SupportTicket::where('sender_user_id', auth()->id())->latest()->paginate(20));
    }

    public function user_view_details($id)
    {
        $ticket = SupportTicket::where('sender_user_id', auth()->id())->with('replies')->findOrFail($id);
        return response()->json($ticket);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'support_category_id' => ['nullable', 'integer', 'exists:support_categories,id'],
            'preferred_language' => ['nullable','in:en,ur,ar,hi'],
        ]);
        if (! \Illuminate\Support\Facades\Schema::hasColumn('support_tickets','preferred_language')) {
            unset($data['preferred_language']);
        }
        SupportTicket::create($data + [
            'sender_user_id' => auth()->id(),
            'ticket_id' => now()->format('YmdHis').'-'.random_int(1000, 9999),
            'status' => 0,
            'expected_response_at' => now()->addHours(max(1, min(168, (int) get_setting('support_first_response_hours', 24)))),
        ]);
        return back()->with('success', 'Support ticket created.');
    }

    public function ticket_reply(Request $request)
    {
        $data = $request->validate([
            'support_ticket_id' => ['required', 'integer', 'exists:support_tickets,id'],
            'reply' => ['required', 'string', 'max:10000'],
            'status' => ['nullable', 'integer', 'in:0,1,2,3'],
        ]);
        $isAdminReply = $request->routeIs('support-ticket.admin_reply');
        DB::transaction(function () use ($data, $isAdminReply): void {
            $ticket = SupportTicket::whereKey($data['support_ticket_id'])->lockForUpdate()->firstOrFail();
            abort_unless($isAdminReply || (int) $ticket->sender_user_id === (int) auth()->id(), 403);
            if (! $isAdminReply && (string) $ticket->status === '1') {
                throw ValidationException::withMessages(['reply' => 'This ticket is closed. Please create a new support ticket.']);
            }
            SupportTicketReply::create([
                'support_ticket_id' => $ticket->id,
                'replied_user_id' => auth()->id(),
                'reply' => $data['reply'],
            ]);
            if ($isAdminReply && array_key_exists('status', $data)) {
                $ticket->update([
                    'status' => (int) $data['status'],
                    'resolved_at' => (int) $data['status'] === 1 ? now() : null,
                ]);
            }
        });
        return back()->with('success', 'Reply saved.');
    }

    public function rate(Request $request, $id)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($id, $data): void {
            $ticket = SupportTicket::whereKey($id)->where('sender_user_id', auth()->id())->lockForUpdate()->firstOrFail();
            if ((string) $ticket->status !== '1') {
                throw ValidationException::withMessages(['rating' => 'Only closed tickets can be rated.']);
            }
            if ($ticket->rated_at !== null) {
                throw ValidationException::withMessages(['rating' => 'This ticket has already been rated.']);
            }
            $ticket->update(['rating' => $data['rating'], 'rating_comment' => $data['comment'] ?? null, 'rated_at' => now()]);
        });
        return back()->with('success', 'Thank you for rating support.');
    }

    public function default_ticket_assigned_agent()
    {
        return back()->with('status', 'Configure the assigned support agent in admin settings.');
    }

    public function destroy($id)
    {
        SupportTicket::findOrFail($id)->delete();
        return back()->with('success', 'Support ticket deleted.');
    }
}
