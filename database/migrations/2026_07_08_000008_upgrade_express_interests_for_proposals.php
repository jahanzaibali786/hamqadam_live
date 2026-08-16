<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('express_interests', 'initial_note')) {
            Schema::table('express_interests', function (Blueprint $table) {
                $table->text('initial_note')->nullable()->after('status');
                $table->timestamp('responded_at')->nullable()->after('initial_note');
                $table->timestamp('withdrawn_at')->nullable()->after('responded_at');
                $table->timestamp('cancelled_at')->nullable()->after('withdrawn_at');

                $table->index(['interested_by', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        Schema::dropIfExists('proposal_notes');
        Schema::dropIfExists('proposal_events');

        Schema::create('proposal_notes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('express_interest_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('note');
            $table->timestamps();

            $table->index(['express_interest_id', 'created_at']);
            $table->foreign('express_interest_id')->references('id')->on('express_interests')->cascadeOnDelete();
        });

        Schema::create('proposal_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('express_interest_id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['express_interest_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->foreign('express_interest_id')->references('id')->on('express_interests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_events');
        Schema::dropIfExists('proposal_notes');

        Schema::table('express_interests', function (Blueprint $table) {
            $table->dropIndex(['interested_by', 'status']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn(['initial_note', 'responded_at', 'withdrawn_at', 'cancelled_at']);
        });
    }
};
