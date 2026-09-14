<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'trust_badge')) {
                $table->boolean('trust_badge')->default(false)->after('verification_status')->index();
            }
            if (! Schema::hasColumn('members', 'trust_badge_earned_at')) {
                $table->timestamp('trust_badge_earned_at')->nullable()->after('trust_badge');
            }
            if (! Schema::hasColumn('members', 'verification_badge')) {
                $table->boolean('verification_badge')->default(false)->after('trust_badge_earned_at')->index();
            }
            if (! Schema::hasColumn('members', 'verification_badge_earned_at')) {
                $table->timestamp('verification_badge_earned_at')->nullable()->after('verification_badge');
            }
        });

        DB::table('members')
            ->where(function ($query): void {
                $query->where('verification_status', 'verified')
                    ->orWhere('ai_verification_status', 'approved');
            })
            ->update([
                'verification_badge' => true,
                'verification_badge_earned_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', function (Blueprint $table): void {
            foreach ([
                'verification_badge_earned_at',
                'verification_badge',
                'trust_badge_earned_at',
                'trust_badge',
            ] as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};