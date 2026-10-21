<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Http\Controllers\Admin\PaymentRefundController;
use App\Models\PaymentRefundRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** No staff checkbox may turn an unverified demo charge into an actual provider refund. */
final class QaRefundAdminSafetyTest extends TestCase
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
        Schema::create('package_payments', function (Blueprint $t): void {
            $t->id(); $t->string('payment_method');
            $t->softDeletes(); $t->timestamps();
        });
        Schema::create('payment_refund_requests', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('package_payment_id')->unique();
            $t->unsignedBigInteger('user_id');
            $t->string('status')->default('requested');
            $t->decimal('amount', 12, 2); $t->string('currency');
            $t->text('reason'); $t->string('provider_refund_reference')->nullable();
            $t->text('admin_note')->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable(); $t->timestamps();
        });
        DB::table('package_payments')->insert(['id'=>101,'payment_method'=>'qa_demo_no_provider']);
        DB::table('payment_refund_requests')->insert([
            'id'=>10,'package_payment_id'=>101,'user_id'=>1,
            'status'=>'requested','amount'=>699,'currency'=>'PKR','reason'=>'QA DEMO refund review',
        ]);
    }

    private function adminRequest(array $data): Request
    {
        $request = Request::create('/admin/payment-refunds/10/review', 'POST', $data);
        $actor = new User(); $actor->id = 99;
        $request->setUserResolver(static fn () => $actor);
        return $request;
    }

    public function test_fake_provider_confirmation_on_non_stripe_invoice_is_rejected(): void
    {
        $refund = PaymentRefundRequest::query()->findOrFail(10);
        try {
            app(PaymentRefundController::class)->review($this->adminRequest([
                'status'=>'provider_refund_confirmed',
                'provider_verified'=>'1',
                'provider_refund_reference'=>'re_fake_qa_reference',
            ]), $refund);
            $this->fail('Synthetic payment must never be marked provider-refunded.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
            $this->assertDatabaseHas('payment_refund_requests', [
                'id'=>10,'status'=>'requested','provider_refund_reference'=>null,
            ]);
        }
    }

    public function test_provider_confirmation_requires_verified_checkbox(): void
    {
        $refund = PaymentRefundRequest::query()->findOrFail(10);
        try {
            app(PaymentRefundController::class)->review($this->adminRequest([
                'status' => 'provider_refund_confirmed',
                'provider_refund_reference' => 're_fake_qa_reference',
            ]), $refund);
            $this->fail('Provider confirmation must require an accepted verification checkbox.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('provider_verified', $e->errors());
            $this->assertDatabaseHas('payment_refund_requests', ['id' => 10, 'status' => 'requested']);
        }
    }

    public function test_rejection_does_not_require_provider_confirmation(): void
    {
        app(PaymentRefundController::class)->review($this->adminRequest([
            'status' => 'rejected',
            'admin_note' => 'QA: rejected after administrative review.',
        ]), PaymentRefundRequest::query()->findOrFail(10));

        $this->assertDatabaseHas('payment_refund_requests', [
            'id' => 10,
            'status' => 'rejected',
            'provider_refund_reference' => null,
            'reviewed_by' => 99,
        ]);
    }

    public function test_admin_reviewing_updates_status_but_does_not_issue_a_refund(): void
    {
        app(PaymentRefundController::class)->review($this->adminRequest([
            'status'=>'reviewing', 'admin_note'=>'QA review without refund execution',
        ]), PaymentRefundRequest::query()->findOrFail(10));
        $this->assertDatabaseHas('payment_refund_requests', [
            'id'=>10,'status'=>'reviewing', 'provider_refund_reference'=>null,
            'reviewed_by'=>99,
        ]);
    }
}

