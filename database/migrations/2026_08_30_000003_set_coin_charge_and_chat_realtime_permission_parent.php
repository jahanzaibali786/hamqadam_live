<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        foreach (["coin_charge_settings", "chat_realtime_settings"] as $permissionName) {
            Permission::query()
                ->where("name", $permissionName)
                ->update(["parent" => "settings"]);
        }
    }

    public function down(): void
    {
        foreach (["coin_charge_settings", "chat_realtime_settings"] as $permissionName) {
            Permission::query()
                ->where("name", $permissionName)
                ->update(["parent" => null]);
        }
    }
};

