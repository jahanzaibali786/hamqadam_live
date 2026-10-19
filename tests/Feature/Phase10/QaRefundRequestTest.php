<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Http\Controllers\PaymentRefundWebController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Direct controller tests, real Eloquent persistence, private SQLite memory DB. */
final class QaRefundRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('package_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id');
            $t->string('payment_status'); $t->decimal('payable_amount',12,2);
            $t->string('currency')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('payment_refund_requests', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('package_payment_id')->unique();
            $t->unsignedBigInteger('user_id'); $t->string('status');
            $t->decimal('amount',12,2); $t->string('currency'); $t->text('reason');
            $t->string('provider_refund_reference')->nullable();
            $t->text('admin_note')->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->timestamps();
        });
        DB::table('package_payments')->insert([
            ['id'=>101, 'user_id'=>1, 'payment_status'=>'Paid','payable_amount'=>699,'currency'=>'PKR'],
            ['id'=>102, 'user_id'=>1, 'payment_status'=>'Due','payable_amount'=>699,'currency'=>'PKR'],
        ]);
    }

    private function requestAs(int $id, int $payment): Request
    {
        $user = new User(); $user->id = $id;
        $request = Request::create('/member/payment-refunds', 'POST', [
            'payment_id' => $payment,
            'reason' => 'QA DEMO — please review this charge.',
        ]);
        $request->headers->set('Referer','/member/billing/refunds');
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_paid_refund_request_is_unique_and_never_marks_provider_refunded(): void
    {
        $controller = new PaymentRefundWebController();
        $controller->store($this->requestAs(1, 101));
        $controller->store($this->requestAs(1, 101));
        $this->assertSame(1, DB::table('payment_refund_requests')->count());
        $this->assertDatabaseHas('payment_refund_requests', [
            'package_payment_id' => 101, 'status' => 'requested', 'provider_refund_reference' => null,
        ]);
    }

    public function test_member_cannot_request_refund_for_someone_elses_invoice(): void
    {
        $this->expectException(ModelNotFoundException::class);
        (new PaymentRefundWebController())->store($this->requestAs(2, 101));
    }

    public function test_due_invoice_cannot_be_refunded(): void
    {
        $this->expectException(ValidationException::class);
        (new PaymentRefundWebController())->store($this->requestAs(1, 102));
    }
}

