<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('event_type', 50)->index();
                $table->string('guard', 30)->nullable();
                $table->string('session_id')->nullable()->index();
                $table->string('device_type', 30)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('location')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['user_id', 'event_type', 'occurred_at'], 'user_activity_user_event_time_idx');
            });
        }

        $permission = Permission::findOrCreate('view_user_activity', 'web');
        Permission::query()->where('name', 'view_user_activity')->update(['parent' => 'Members']);

        Role::query()
            ->whereIn('name', ['admin', 'Admin', 'Super Admin', 'super_admin'])
            ->get()
            ->each(fn ($role) => $role->givePermissionTo($permission));
    }

    public function down(): void
    {
        Permission::query()->where('name', 'view_user_activity')->delete();
        Schema::dropIfExists('user_activity_logs');
    }
};
