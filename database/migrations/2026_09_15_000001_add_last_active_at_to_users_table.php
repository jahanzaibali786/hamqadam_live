<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat presence: the last moment the member actively used the app.
 *
 * Written by App\Http\Middleware\EnsureApiMemberActivity on every
 * authenticated API request (throttled to once a minute per member) and read by
 * the chat resources so the app can show "Online" / "Last seen …" under the
 * conversation header.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_active_at')->nullable()->after('last_login_at');
            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['last_active_at']);
            $table->dropColumn('last_active_at');
        });
    }
};
