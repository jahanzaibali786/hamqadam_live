<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PackagePayment;
use App\Models\PaymentWebhookEvent;
use App\Models\PaymentRefundRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only consistency checks: never marks payments paid or issues refunds.
 * Nonzero exit signals that manual reconciliation is required.
 */
final class AuditPaymentIntegrity extends Command
{
    protected $signature = 'payments:audit {--fail-on-anomaly : Return a failure code when anomalies exist}';
    protected $description = 'Read-only reconciliation checks for payments, gateway references, events and refunds';

    public function handle(): int
    {
        foreach (['package_payments', 'payment_webhook_events', 'payment_refund_requests'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Missing table {$table}; run pending migrations first.");
                return self::FAILURE;
            }
        }
        $checks = [
            'Paid payments without a paid timestamp' => PackagePayment::where('payment_status', 'Paid')->whereNull('paid_at')->count(),
            'Due payments older than 24 hours' => PackagePayment::where('payment_status', 'Due')->where('created_at', '<', now()->subDay())->count(),
            'Webhook records not processed' => PaymentWebhookEvent::whereNull('processed_at')->count(),
            'Refunds awaiting review' => PaymentRefundRequest::whereIn('status', ['requested','reviewing'])->count(),
            'Duplicate gateway references' => DB::table('package_payments')->whereNotNull('gateway_reference')
                ->select('payment_method', 'gateway_reference')
                ->groupBy('payment_method', 'gateway_reference')
                ->havingRaw('COUNT(*) > 1')->get()->count(),
            'Confirmed refunds missing provider references' => PaymentRefundRequest::where('status', 'provider_refund_confirmed')
                ->where(fn ($q) => $q->whereNull('provider_refund_reference')->orWhere('provider_refund_reference', ''))->count(),
        ];
        $this->table(['Reconciliation check', 'Count'], collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all());
        $attention = array_sum(array_values($checks));
        $this->line('Review required for ' . $attention . ' records/groups (including pending refunds).');
        return $this->option('fail-on-anomaly') && $attention > 0 ? self::FAILURE : self::SUCCESS;
    }
}
