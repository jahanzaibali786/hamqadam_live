<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (! Schema::hasColumn('packages', 'plan_tier')) {
                $table->string('plan_tier')->nullable()->after('name');
                $table->json('feature_flags')->nullable()->after('validity');
                $table->boolean('is_recurring')->default(false)->after('feature_flags');
            }
        });

        Schema::create('payment_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 12, 2);
            $table->decimal('minimum_amount', 12, 2)->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'expires_at'], 'coupons_active_expires_idx');
        });

        Schema::table('package_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('package_payments', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained('payment_coupons')->nullOnDelete();
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('payable_amount', 12, 2)->nullable();
                $table->string('currency', 3)->default('PKR');
                $table->string('gateway_reference')->nullable();
                $table->string('gateway_status')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('subscription_ends_at')->nullable();
                $table->string('invoice_number')->nullable()->unique();
                $table->json('metadata')->nullable();

                $table->index(['user_id', 'payment_status'], 'payments_user_status_idx');
                $table->index(['payment_method', 'gateway_reference'], 'payments_gateway_ref_idx');
            }
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id'], 'pwe_gateway_event_unique');
            $table->index(['gateway', 'event_type'], 'pwe_gateway_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');

        Schema::table('package_payments', function (Blueprint $table) {
            if (Schema::hasColumn('package_payments', 'coupon_id')) {
                $table->dropIndex('payments_user_status_idx');
                $table->dropIndex('payments_gateway_ref_idx');
                $table->dropUnique(['invoice_number']);
                $table->dropForeign(['coupon_id']);
                $table->dropColumn([
                    'coupon_id',
                    'discount_amount',
                    'payable_amount',
                    'currency',
                    'gateway_reference',
                    'gateway_status',
                    'paid_at',
                    'subscription_ends_at',
                    'invoice_number',
                    'metadata',
                ]);
            }
        });

        Schema::dropIfExists('payment_coupons');

        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'plan_tier')) {
                $table->dropColumn(['plan_tier', 'feature_flags', 'is_recurring']);
            }
        });
    }
};
