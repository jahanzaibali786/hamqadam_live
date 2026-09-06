<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('review_member_verification', 'web');
        Permission::query()
            ->where('name', 'review_member_verification')
            ->update(['parent' => 'Members']);

        Role::query()
            ->whereIn('name', ['admin', 'Admin', 'Super Admin', 'super_admin'])
            ->get()
            ->each(fn ($role) => $role->givePermissionTo($permission));
    }

    public function down(): void
    {
        Permission::query()->where('name', 'review_member_verification')->delete();
    }
};
