<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_expectations', function (Blueprint $table) {
            $columns = [
                'preferred_age_min' => fn () => $table->unsignedTinyInteger('preferred_age_min')->nullable()->after('general'),
                'preferred_age_max' => fn () => $table->unsignedTinyInteger('preferred_age_max')->nullable()->after('preferred_age_min'),
                'height_min' => fn () => $table->decimal('height_min', 4, 2)->nullable()->after('height'),
                'height_max' => fn () => $table->decimal('height_max', 4, 2)->nullable()->after('height_min'),
                'income_min' => fn () => $table->decimal('income_min', 12, 2)->nullable()->after('profession'),
                'income_max' => fn () => $table->decimal('income_max', 12, 2)->nullable()->after('income_min'),
                'preferred_city_id' => fn () => $table->unsignedBigInteger('preferred_city_id')->nullable()->after('preferred_state_id'),
                'preferred_language_ids' => fn () => $table->json('preferred_language_ids')->nullable()->after('language_id'),
                'lifestyle' => fn () => $table->string('lifestyle', 100)->nullable()->after('diet'),
                'prayer' => fn () => $table->string('prayer', 100)->nullable()->after('lifestyle'),
                'religious_practice' => fn () => $table->string('religious_practice', 100)->nullable()->after('prayer'),
                'children_preference' => fn () => $table->string('children_preference', 50)->nullable()->after('children_acceptable'),
                'deal_breakers' => fn () => $table->json('deal_breakers')->nullable()->after('complexion'),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('partner_expectations', $column)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_expectations', function (Blueprint $table) {
            foreach ([
                'preferred_age_min',
                'preferred_age_max',
                'height_min',
                'height_max',
                'income_min',
                'income_max',
                'preferred_city_id',
                'preferred_language_ids',
                'lifestyle',
                'prayer',
                'religious_practice',
                'children_preference',
                'deal_breakers',
            ] as $column) {
                if (Schema::hasColumn('partner_expectations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

