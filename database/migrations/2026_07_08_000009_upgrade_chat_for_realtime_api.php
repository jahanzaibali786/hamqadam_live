<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_threads', 'message_request_status')) {
                $table->string('message_request_status')->default('accepted')->after('blocked_by_user');
                $table->timestamp('last_message_at')->nullable()->after('message_request_status');
                $table->timestamp('sender_muted_at')->nullable()->after('last_message_at');
                $table->timestamp('receiver_muted_at')->nullable()->after('sender_muted_at');
                $table->index(['sender_user_id', 'receiver_user_id']);
                $table->index(['last_message_at']);
            }
        });

        Schema::table('chats', function (Blueprint $table) {
            if (! Schema::hasColumn('chats', 'message_type')) {
                $table->string('message_type')->default('text')->after('message');
                $table->bigInteger('reply_to_chat_id')->nullable()->after('message_type');
                $table->timestamp('delivered_at')->nullable()->after('seen');
                $table->timestamp('read_at')->nullable()->after('delivered_at');
                $table->timestamp('deleted_by_sender_at')->nullable()->after('read_at');
                $table->timestamp('deleted_by_receiver_at')->nullable()->after('deleted_by_sender_at');
                $table->string('moderation_status')->default('clean')->after('deleted_by_receiver_at');
                $table->decimal('toxicity_score', 5, 2)->nullable()->after('moderation_status');
                $table->json('metadata')->nullable()->after('toxicity_score');
                $table->index(['chat_thread_id', 'created_at']);
                $table->index(['sender_user_id', 'seen']);
            }
        });

        Schema::dropIfExists('chat_typing_indicators');

        Schema::create('chat_typing_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_thread_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['chat_thread_id', 'user_id']);
            $table->index(['chat_thread_id', 'expires_at']);
            $table->foreign('chat_thread_id')->references('id')->on('chat_threads')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_typing_indicators');

        Schema::table('chats', function (Blueprint $table) {
            if (Schema::hasColumn('chats', 'message_type')) {
                $table->dropIndex(['chat_thread_id', 'created_at']);
                $table->dropIndex(['sender_user_id', 'seen']);
                $table->dropColumn([
                    'message_type',
                    'reply_to_chat_id',
                    'delivered_at',
                    'read_at',
                    'deleted_by_sender_at',
                    'deleted_by_receiver_at',
                    'moderation_status',
                    'toxicity_score',
                    'metadata',
                ]);
            }
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            if (Schema::hasColumn('chat_threads', 'message_request_status')) {
                $table->dropIndex(['sender_user_id', 'receiver_user_id']);
                $table->dropIndex(['last_message_at']);
                $table->dropColumn([
                    'message_request_status',
                    'last_message_at',
                    'sender_muted_at',
                    'receiver_muted_at',
                ]);
            }
        });
    }
};
