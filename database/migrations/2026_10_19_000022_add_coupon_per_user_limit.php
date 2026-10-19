<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_coupons') && ! Schema::hasColumn('payment_coupons','per_user_limit')) {
            Schema::table('payment_coupons', fn (Blueprint $t) => $t->unsignedSmallInteger('per_user_limit')->nullable());
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('payment_coupons','per_user_limit')) {
            Schema::table('payment_coupons',fn (Blueprint $t) => $t->dropColumn('per_user_limit'));
        }
    }
};
