<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_matches', 'score_breakdown')) {
                $table->json('score_breakdown')->nullable()->after('match_percentage');
            }

            if (! Schema::hasColumn('profile_matches', 'compatibility_reasons')) {
                $table->json('compatibility_reasons')->nullable()->after('score_breakdown');
            }

            if (! Schema::hasColumn('profile_matches', 'compatibility_explanation')) {
                $table->text('compatibility_explanation')->nullable()->after('compatibility_reasons');
            }

            if (! Schema::hasColumn('profile_matches', 'calculated_at')) {
                $table->timestamp('calculated_at')->nullable()->after('compatibility_explanation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profile_matches', function (Blueprint $table) {
            foreach ([
                'score_breakdown',
                'compatibility_reasons',
                'compatibility_explanation',
                'calculated_at',
            ] as $column) {
                if (Schema::hasColumn('profile_matches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

