<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| HamQadam Help Center — the real-time support chat behind the app's Help
| button.
|
| Two tables:
|
|   help_chat_threads    one per member, created lazily on their first
|                        message. Every conversation a member opens with
|                        the Help Center continues on the same thread, so
|                        the admin panel sees one list per member rather
|                        than one per complaint.
|   help_chat_messages   the messages themselves. Reuses the uploads table
|                        for attachments (comma-separated upload ids), the
|                        same convention `chats.attachment` uses.
|
| Unread bookkeeping is deliberately two-sided: `admin_unread_count` is what
| the admin panel badges, `user_unread_count` is what the app badges. Each
| side only ever touches its own column, and opening the thread zeroes it.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_chat_threads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('status', 20)->default('open'); // open | closed
            $table->unsignedInteger('admin_unread_count')->default(0);
            $table->unsignedInteger('user_unread_count')->default(0);
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('status');
            $table->index('last_message_at');
        });

        Schema::create('help_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('sender_user_id');
            $table->text('message')->nullable();
            $table->string('message_type', 20)->default('text'); // text | file
            $table->text('attachment')->nullable(); // comma-separated upload ids
            $table->unsignedTinyInteger('seen')->default(0);
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('thread_id')->references('id')->on('help_chat_threads')->cascadeOnDelete();
            $table->foreign('sender_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_chat_messages');
        Schema::dropIfExists('help_chat_threads');
    }
};
