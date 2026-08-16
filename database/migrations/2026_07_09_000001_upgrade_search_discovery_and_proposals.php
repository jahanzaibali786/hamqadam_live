<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('filters')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('hidden_profile_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hidden_from_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'hidden_from_user_id'], 'hidden_profile_user_unique');
            $table->index(['hidden_from_user_id', 'user_id'], 'hidden_profile_reverse_idx');
        });

        Schema::create('match_suggestion_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('suggested_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('feedback');
            $table->string('source')->default('daily_recommendation');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'suggested_user_id'], 'match_feedback_unique');
            $table->index(['feedback', 'created_at'], 'match_feedback_created_idx');
        });

        Schema::table('profile_privacy_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('profile_privacy_settings', 'do_not_disturb')) {
                $table->boolean('do_not_disturb')->default(false)->after('allow_profile_view_notifications');
            }

            if (! Schema::hasColumn('profile_privacy_settings', 'invisible_mode')) {
                $table->boolean('invisible_mode')->default(false)->after('do_not_disturb');
            }
        });

        Schema::table('express_interests', function (Blueprint $table) {
            if (! Schema::hasColumn('express_interests', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('cancelled_at');
            }

            if (! Schema::hasColumn('express_interests', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('express_interests', 'compatibility_snapshot')) {
                $table->unsignedTinyInteger('compatibility_snapshot')->nullable()->after('expired_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('express_interests', function (Blueprint $table) {
            foreach (['expires_at', 'expired_at', 'compatibility_snapshot'] as $column) {
                if (Schema::hasColumn('express_interests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('profile_privacy_settings', function (Blueprint $table) {
            foreach (['do_not_disturb', 'invisible_mode'] as $column) {
                if (Schema::hasColumn('profile_privacy_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('match_suggestion_feedback');
        Schema::dropIfExists('hidden_profile_users');
        Schema::dropIfExists('search_histories');
    }
};
