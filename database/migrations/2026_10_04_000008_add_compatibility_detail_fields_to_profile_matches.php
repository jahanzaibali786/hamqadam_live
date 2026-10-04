<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'score_breakdown_their' => fn (Blueprint $table) => $table->json('score_breakdown_their')->nullable(),
            'mutual_matched_preferences' => fn (Blueprint $table) => $table->json('mutual_matched_preferences')->nullable(),
            'one_sided_preferences' => fn (Blueprint $table) => $table->json('one_sided_preferences')->nullable(),
            'score_balance' => fn (Blueprint $table) => $table->json('score_balance')->nullable(),
            'compatibility_concerns' => fn (Blueprint $table) => $table->json('compatibility_concerns')->nullable(),
            'recommended_actions' => fn (Blueprint $table) => $table->json('recommended_actions')->nullable(),
            'confidence_score' => fn (Blueprint $table) => $table->unsignedTinyInteger('confidence_score')->nullable(),
            'model_confidence' => fn (Blueprint $table) => $table->string('model_confidence')->nullable(),
            'ai_enhanced' => fn (Blueprint $table) => $table->boolean('ai_enhanced')->default(false),
            'match_status' => fn (Blueprint $table) => $table->string('match_status')->nullable(),
            'compatibility_level' => fn (Blueprint $table) => $table->string('compatibility_level')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('profile_matches', $column)) {
                Schema::table('profile_matches', $definition);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'score_breakdown_their', 'mutual_matched_preferences', 'one_sided_preferences',
            'score_balance', 'compatibility_concerns', 'recommended_actions',
            'confidence_score', 'model_confidence', 'ai_enhanced', 'match_status',
            'compatibility_level',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('profile_matches', $column)) {
                Schema::table('profile_matches', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
