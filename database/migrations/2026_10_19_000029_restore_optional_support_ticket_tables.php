<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support tickets were previously supplied by an optional addon. Core Hamqadam
 * now links to those pages even when the addon has not installed its tables.
 * Create missing tables without replacing an existing addon installation.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_categories')) {
            Schema::create('support_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (! Schema::hasColumn('support_categories', 'deleted_at')) {
            Schema::table('support_categories', fn (Blueprint $table) => $table->softDeletes());
        }

        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table): void {
                $table->id();
                $table->string('ticket_id', 80)->unique();
                $table->unsignedBigInteger('sender_user_id')->index();
                $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
                $table->unsignedBigInteger('support_category_id')->nullable()->index();
                $table->string('subject');
                $table->text('description')->nullable();
                $table->unsignedTinyInteger('status')->default(0)->index();
                $table->timestamp('expected_response_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('rating_comment')->nullable();
                $table->timestamp('rated_at')->nullable();
                $table->string('preferred_language', 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Previous enhancement migrations may already be marked "Ran"
            // although their addon-dependent schema changes were skipped.
            $fields = [
                'ticket_id' => fn (Blueprint $t) => $t->string('ticket_id', 80)->nullable(),
                'sender_user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('sender_user_id')->nullable()->index(),
                'assigned_user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('assigned_user_id')->nullable()->index(),
                'support_category_id' => fn (Blueprint $t) => $t->unsignedBigInteger('support_category_id')->nullable()->index(),
                'subject' => fn (Blueprint $t) => $t->string('subject')->nullable(),
                'description' => fn (Blueprint $t) => $t->text('description')->nullable(),
                'status' => fn (Blueprint $t) => $t->unsignedTinyInteger('status')->default(0),
                'expected_response_at' => fn (Blueprint $t) => $t->timestamp('expected_response_at')->nullable(),
                'resolved_at' => fn (Blueprint $t) => $t->timestamp('resolved_at')->nullable(),
                'rating' => fn (Blueprint $t) => $t->unsignedTinyInteger('rating')->nullable(),
                'rating_comment' => fn (Blueprint $t) => $t->text('rating_comment')->nullable(),
                'rated_at' => fn (Blueprint $t) => $t->timestamp('rated_at')->nullable(),
                'preferred_language' => fn (Blueprint $t) => $t->string('preferred_language', 2)->nullable(),
                'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
                'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
                'deleted_at' => fn (Blueprint $t) => $t->softDeletes(),
            ];
            foreach ($fields as $column => $define) {
                if (! Schema::hasColumn('support_tickets', $column)) {
                    Schema::table('support_tickets', $define);
                }
            }
        }

        if (! Schema::hasTable('support_ticket_replies')) {
            Schema::create('support_ticket_replies', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('support_ticket_id')->index();
                $table->unsignedBigInteger('replied_user_id')->index();
                $table->text('reply');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            if (! Schema::hasColumn('support_ticket_replies', 'deleted_at')) {
                Schema::table('support_ticket_replies', fn (Blueprint $t) => $t->softDeletes());
            }
        }
    }

    public function down(): void
    {
        // Deliberately no destructive down(): tables may be shared with a
        // previously installed add-on and may now contain real support data.
        // Preserve support history unless a separately reviewed archival
        // migration explicitly removes it.
    }
};
