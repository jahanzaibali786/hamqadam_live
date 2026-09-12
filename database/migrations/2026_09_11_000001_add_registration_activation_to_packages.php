<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('packages', 'activate_on_registration')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->boolean('activate_on_registration')
                    ->default(false)
                    ->after('active')
                    ->index();
            });
        }

        if (Schema::hasColumn('packages', 'activate_on_registration')) {
            $selected = DB::table('packages')
                ->where('active', 1)
                ->where(function ($query): void {
                    $query->where('name', 'Basic Free')
                        ->orWhere('plan_tier', 'free')
                        ->orWhere('price', 0);
                })
                ->orderByRaw("CASE WHEN name = 'Basic Free' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->value('id');

            $selected ??= DB::table('packages')
                ->where('active', 1)
                ->orderBy('id')
                ->value('id');

            DB::table('packages')->update(['activate_on_registration' => false]);

            if ($selected) {
                DB::table('packages')
                    ->where('id', $selected)
                    ->update(['activate_on_registration' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('packages', 'activate_on_registration')) {
            Schema::table('packages', function (Blueprint $table): void {
                $table->dropColumn('activate_on_registration');
            });
        }
    }
};
