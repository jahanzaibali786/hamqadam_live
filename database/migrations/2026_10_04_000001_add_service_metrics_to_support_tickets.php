<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Support tickets are provided by an optional addon in some installs.
        // Do not make the core application migration chain fail when that
        // addon is not installed yet.
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('support_tickets', 'expected_response_at')) {
                $table->timestamp('expected_response_at')->nullable()->after('status');
                $table->timestamp('resolved_at')->nullable()->after('expected_response_at');
                $table->unsignedTinyInteger('rating')->nullable()->after('resolved_at');
                $table->text('rating_comment')->nullable()->after('rating');
                $table->timestamp('rated_at')->nullable()->after('rating_comment');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('support_tickets', 'expected_response_at') ? 'expected_response_at' : null,
                Schema::hasColumn('support_tickets', 'resolved_at') ? 'resolved_at' : null,
                Schema::hasColumn('support_tickets', 'rating') ? 'rating' : null,
                Schema::hasColumn('support_tickets', 'rating_comment') ? 'rating_comment' : null,
                Schema::hasColumn('support_tickets', 'rated_at') ? 'rated_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
