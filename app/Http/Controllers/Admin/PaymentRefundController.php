<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Internal review tracker. A gateway refund must be performed/verified separately. */
final class PaymentRefundController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable','in:requested,reviewing,rejected,provider_refund_confirmed']]);
        $items = PaymentRefundRequest::query()->with(['user','payment'])
            ->when(! empty($data['status']), fn($q) => $q->where('status',$data['status']))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.payment_refunds.index', compact('items'));
    }

    public function review(Request $request, PaymentRefundRequest $refund): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required','in:reviewing,rejected,provider_refund_confirmed'],
            'admin_note' => ['nullable','string','max:2000'],
            'provider_refund_reference' => ['required_if:status,provider_refund_confirmed','nullable','string','max:255'],
            'provider_verified' => ['required_if:status,provider_refund_confirmed','nullable','accepted'],
        ]);
        if ($refund->status === 'provider_refund_confirmed') return back()->withErrors(['status' => 'A confirmed record cannot be rewritten.']);
        $refund->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'provider_refund_reference' => $data['provider_refund_reference'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        return back()->with('success', 'Refund review status updated. Subscription entitlements remain unchanged pending policy review.');
    }
}
