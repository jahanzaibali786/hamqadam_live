<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gift module. Coins come from the EXISTING package system
 * (members.remaining_interest — the same balance proposals/shortlists spend),
 * so there is no second wallet here — only a gift catalog and gift
 * transactions that reference PackageUsage entries for the audit trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gifts')) {
            Schema::create('gifts', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('thumbnail');        // public path of the small image
                $table->string('animated_asset');   // public path of the Lottie JSON
                $table->unsignedInteger('coins');   // price lives HERE — never in the client
                $table->string('category')->default('general');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'sort_order'], 'gifts_active_sort_idx');
            });
        }

        if (! Schema::hasTable('gift_transactions')) {
            Schema::create('gift_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('gift_id')->constrained('gifts')->cascadeOnDelete();
                $table->unsignedInteger('coins');   // snapshot of the gift's price at send time
                $table->text('message')->nullable();
                $table->string('status')->default('sent'); // sent | failed
                $table->timestamps();

                $table->index(['receiver_id', 'created_at'], 'gift_tx_receiver_idx');
                $table->index(['sender_id', 'created_at'], 'gift_tx_sender_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_transactions');
        Schema::dropIfExists('gifts');
    }
};
