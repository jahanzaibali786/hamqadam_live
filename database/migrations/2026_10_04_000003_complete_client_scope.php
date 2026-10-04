<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sponsored_listings')) {
            Schema::create('sponsored_listings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('sponsor_name')->nullable();
                $table->string('image')->nullable();
                $table->string('target_url')->nullable();
                $table->string('placement')->default('discovery');
                $table->unsignedInteger('priority')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->json('eligible_plan_ids')->nullable();
                $table->unsignedBigInteger('impressions')->default(0);
                $table->unsignedBigInteger('clicks')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reward_rules')) {
            Schema::create('reward_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('event_key')->unique();
                $table->string('title');
                $table->unsignedInteger('coin_reward')->default(0);
                $table->unsignedInteger('max_per_user')->default(1);
                $table->boolean('active')->default(true);
                $table->json('conditions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reward_transactions')) {
            Schema::create('reward_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reward_rule_id')->nullable()->constrained('reward_rules')->nullOnDelete();
                $table->string('event_key');
                $table->integer('coins');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'event_key']);
            });
        }

        if (! Schema::hasTable('analytics_events')) {
            Schema::create('analytics_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_name', 100);
                $table->string('feature_key', 100)->nullable();
                $table->string('session_key', 120)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('region', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('platform', 40)->nullable();
                $table->string('source', 80)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->useCurrent();
                $table->timestamps();
                $table->index(['event_name', 'occurred_at']);
                $table->index(['feature_key', 'occurred_at']);
            });
        }

        if (! Schema::hasTable('satisfaction_surveys')) {
            Schema::create('satisfaction_surveys', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('score');
                $table->text('comment')->nullable();
                $table->string('context', 80)->default('nps');
                $table->string('platform', 40)->nullable();
                $table->timestamps();
                $table->index(['context', 'created_at']);
            });
        }

        if (! Schema::hasTable('match_successes')) {
            Schema::create('match_successes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('matched_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('profile_match_id')->nullable()->constrained('profile_matches')->nullOnDelete();
                $table->string('status', 40)->default('got_match');
                $table->text('note')->nullable();
                $table->timestamp('confirmed_at')->useCurrent();
                $table->timestamps();
                $table->unique(['user_id', 'matched_user_id', 'status'], 'match_success_unique');
            });
        }

        if (Schema::hasTable('express_interests')) {
            Schema::table('express_interests', function (Blueprint $table): void {
                if (! Schema::hasColumn('express_interests', 'is_priority')) {
                    $table->boolean('is_priority')->default(false)->after('initial_note');
                }
                if (! Schema::hasColumn('express_interests', 'priority_expires_at')) {
                    $table->timestamp('priority_expires_at')->nullable()->after('is_priority');
                }
            });
        }

        if (Schema::hasTable('notification_delivery_logs')) {
            Schema::table('notification_delivery_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('notification_delivery_logs', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('sent_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('delivered_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'clicked_at')) {
                    $table->timestamp('clicked_at')->nullable()->after('read_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'deep_link')) {
                    $table->string('deep_link')->nullable()->after('clicked_at');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'reminder_count')) {
                    $table->unsignedTinyInteger('reminder_count')->default(0)->after('deep_link');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'last_reminded_at')) {
                    $table->timestamp('last_reminded_at')->nullable()->after('reminder_count');
                }
                if (! Schema::hasColumn('notification_delivery_logs', 'failure_reason')) {
                    $table->text('failure_reason')->nullable()->after('last_reminded_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['match_successes', 'satisfaction_surveys', 'analytics_events', 'reward_transactions', 'reward_rules', 'sponsored_listings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
