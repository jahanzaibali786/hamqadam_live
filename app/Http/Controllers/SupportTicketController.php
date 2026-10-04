<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;

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
        return view()->exists('frontend.member.dashboard')
            ? view('frontend.member.dashboard')
            : redirect()->route('dashboard');
    }

    public function user_index()
    {
        return response()->json(SupportTicket::where('sender_user_id', auth()->id())->latest()->paginate(20));
    }

    public function user_view_details($id)
    {
        return response()->json(SupportTicket::findOrFail($id));
    }

    public function store(Request $request)
    {
        $ticket = SupportTicket::create($request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'support_category_id' => ['nullable', 'integer'],
        ]) + [
            'sender_user_id' => auth()->id(),
            'ticket_id' => date('YmdHis'),
            'status' => 0,
            'expected_response_at' => now()->addHours((int) get_setting('support_first_response_hours', 24)),
        ]);

        flash(translate('Support Ticket has been sent successfully'))->success();

        return back()->with('support_ticket_id', $ticket->id);
    }

    public function ticket_reply(Request $request)
    {
        $data = $request->validate([
            'support_ticket_id' => ['required', 'integer'],
            'reply' => ['required', 'string'],
            'status' => ['nullable'],
        ]);

        SupportTicketReply::create([
            'support_ticket_id' => $data['support_ticket_id'],
            'replied_user_id' => auth()->id(),
            'reply' => $data['reply'],
        ]);

        if (array_key_exists('status', $data)) {
            SupportTicket::whereKey($data['support_ticket_id'])->update([
                'status' => $data['status'],
                'resolved_at' => (string) $data['status'] === '1' ? now() : null,
            ]);
        }

        flash(translate('Reply has been sent successfully'))->success();

        return back();
    }

    public function rate(Request $request, $id)
    {
        $ticket = SupportTicket::whereKey($id)->where('sender_user_id', auth()->id())->firstOrFail();
        abort_unless((string) $ticket->status === '1', 422, translate('Only resolved tickets can be rated.'));

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $ticket->update(['rating' => $data['rating'], 'rating_comment' => $data['comment'] ?? null, 'rated_at' => now()]);
        flash(translate('Thank you for rating our support.'))->success();

        return back();
    }

    public function default_ticket_assigned_agent()
    {
        flash(translate('Default support agent can be configured from settings.'))->success();

        return back();
    }

    public function destroy($id)
    {
        SupportTicket::destroy($id);

        flash(translate('Support Ticket has been deleted successfully'))->success();

        return back();
    }
}
