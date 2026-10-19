<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PackagePayment;
use App\Models\PaymentRefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Refund request intake, not provider refund execution. */
final class PaymentRefundWebController extends Controller
{
    public function index(Request $request): View
    {
        $payments = PackagePayment::query()->where('user_id', $request->user()->id)
            ->where('payment_status', 'Paid')->where('payable_amount','>',0)
            ->latest()->limit(40)->get();
        $requests = PaymentRefundRequest::query()->where('user_id', $request->user()->id)
            ->latest()->paginate(20);
        return view('frontend.member.billing.refunds', compact('payments','requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_id' => ['required','integer'],
            'reason' => ['required','string','min:10','max:2000'],
        ]);
        DB::transaction(function () use ($request, $data): void {
            $payment = PackagePayment::query()->where('user_id', $request->user()->id)
                ->whereKey($data['payment_id'])->lockForUpdate()->firstOrFail();
            if ($payment->payment_status !== 'Paid' || (float)$payment->payable_amount <= 0) {
                throw ValidationException::withMessages(['payment_id' => 'Only successfully charged purchases qualify for a review.']);
            }
            PaymentRefundRequest::query()->firstOrCreate(['package_payment_id' => $payment->id], [
                'user_id' => $request->user()->id,
                'status' => 'requested',
                'amount' => $payment->payable_amount,
                'currency' => $payment->currency ?: 'PKR',
                'reason' => $data['reason'],
            ]);
        });
        return back()->with('success','Your refund request has been submitted for review; no funds have been returned yet.');
    }
}
