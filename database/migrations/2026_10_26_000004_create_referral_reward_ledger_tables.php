<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The referral reward services and admin page were added without a
        // migration for either table. Existing installations that created
        // these tables manually remain untouched.
        if (! Schema::hasTable('reward_rules')) {
            Schema::create('reward_rules', function (Blueprint $table) {
                $table->id();
                $table->string('event_key', 100)->unique();
                $table->string('title', 191);
                $table->unsignedInteger('coin_reward')->default(0);
                $table->unsignedInteger('max_per_user')->default(25);
                $table->boolean('active')->default(false);
                $table->json('conditions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reward_transactions')) {
            Schema::create('reward_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reward_rule_id')->nullable()->constrained('reward_rules')->nullOnDelete();
                $table->string('event_key', 100);
                $table->string('reference_type', 100);
                $table->unsignedBigInteger('reference_id');
                $table->unsignedInteger('coins');
                $table->json('metadata')->nullable();
                $table->timestamps();
                // Separate registration and paid-package rewards can each
                // be issued once per referrer + referred member.
                $table->unique(
                    ['user_id', 'event_key', 'reference_type', 'reference_id'],
                    'reward_tx_member_event_reference_unique'
                );
                $table->index(['event_key', 'created_at'], 'reward_tx_event_created_idx');
            });
        }
    }

    public function down(): void
    {
        // A rollback removes only the ledger created by this migration.
        Schema::dropIfExists('reward_transactions');
        Schema::dropIfExists('reward_rules');
    }
};
