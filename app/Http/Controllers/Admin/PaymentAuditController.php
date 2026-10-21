<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagePayment;
use App\Models\PaymentRefundRequest;
use App\Models\PaymentWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Read-only payment audit: never settles, refunds, or changes user balances. */
final class PaymentAuditController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:Due,Paid,Failed,Cancelled'],
            'gateway' => ['nullable', 'in:stripe,easypaisa,jazzcash'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = PackagePayment::query()->with(['user', 'package'])
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($data['gateway'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($data['reference'] ?? null, function ($q, $v): void {
                $q->where('gateway_reference', 'like', '%' . addcslashes($v, '%_\\') . '%');
            })
            ->latest()->paginate(25)->withQueryString();

        $summary = [
            'paid' => PackagePayment::where('payment_status', 'Paid')->count(),
            'due' => PackagePayment::where('payment_status', 'Due')->count(),
            'failed' => PackagePayment::where('payment_status', 'Failed')->count(),
            'cancelled' => PackagePayment::where('payment_status', 'Cancelled')->count(),
            'stale_due' => PackagePayment::where('payment_status', 'Due')->where('created_at', '<', now()->subHours(24))->count(),
            'paid_without_paid_at' => PackagePayment::where('payment_status', 'Paid')->whereNull('paid_at')->count(),
            'paid_refunds' => PaymentRefundRequest::where('status', 'provider_refund_confirmed')->count(),
            'refunds_to_review' => PaymentRefundRequest::whereIn('status', ['requested', 'reviewing'])->count(),
            'unprocessed_webhook_events' => PaymentWebhookEvent::whereNull('processed_at')->count(),
            'duplicate_gateway_references' => DB::table('package_payments')->whereNotNull('gateway_reference')
                ->select('payment_method', 'gateway_reference')
                ->groupBy('payment_method', 'gateway_reference')->havingRaw('COUNT(*) > 1')->get()->count(),
        ];

        return view('admin.payment_audit.index', compact('payments', 'summary'));
    }
}
