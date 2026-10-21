<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) return;
        // The referral module needs this column but it was never created in the
        // main app's migrations. Preserve existing data, and do not rebuild users.
        if (! Schema::hasColumn('users', 'referred_by')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('referred_by')->nullable()->index('users_referred_by_lookup');
            });
        }
        // Historical referral package-discount logic still checks this flag.
        if (! Schema::hasColumn('users', 'referral_comission')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('referral_comission')->default(false);
            });
        }
    }

    public function down(): void
    {
        // No-op: do not remove referral history or silently destroy associations.
    }
};
