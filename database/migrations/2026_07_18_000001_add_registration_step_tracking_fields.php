<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            if (! Schema::hasColumn('members', 'registration_steps')) {
                $table->json('registration_steps')->nullable()->after('verification_status');
            }

            if (! Schema::hasColumn('members', 'registration_completed_at')) {
                $table->timestamp('registration_completed_at')->nullable()->after('registration_steps');
            }
        });

        Schema::table('addresses', function (Blueprint $table): void {
            if (! Schema::hasColumn('addresses', 'area')) {
                $table->string('area')->nullable()->after('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            if (Schema::hasColumn('addresses', 'area')) {
                $table->dropColumn('area');
            }
        });

        Schema::table('members', function (Blueprint $table): void {
            if (Schema::hasColumn('members', 'registration_completed_at')) {
                $table->dropColumn('registration_completed_at');
            }

            if (Schema::hasColumn('members', 'registration_steps')) {
                $table->dropColumn('registration_steps');
            }
        });
    }
};
