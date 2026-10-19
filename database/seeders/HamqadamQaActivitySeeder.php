<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\NotificationDeliveryLog;
use App\Models\SatisfactionSurvey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Synthetic analytics/support examples, visibly marked QA and never sent to providers. */
final class HamqadamQaActivitySeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();
        $users = User::query()->whereIn('email', [
            'demo.user@hamqadam.test', 'ayesha.khan@hamqadam.test',
            'fatima.ahmed@hamqadam.test', 'sana.malik@hamqadam.test',
        ])->orderBy('id')->get();
        if ($users->count() < 2) {
            $this->command?->warn('Two demo-only accounts are required for activity fixtures; skipping synthetic user activity.');
            return;
        }

        if (Schema::hasTable('analytics_events')) {
            $events = [
                ['registration_completed', 'registration', 27],
                ['discovery_opened', 'discovery', 24],
                ['search_performed', 'search', 18],
                ['profile_viewed', 'profile', 12],
                ['interest_sent', 'interest', 7],
                ['chat_started', 'chat', 3],
                ['message_sent', 'chat', 1],
            ];
            foreach ($users as $i => $user) {
                foreach ($events as $j => [$event, $feature, $days]) {
                    $key = "qa-demo-{$user->id}-{$j}";
                    AnalyticsEvent::firstOrCreate(['session_key' => $key, 'source' => 'qa_demo_fixture'], [
                        'user_id' => $user->id, 'event_name' => $event,
                        'feature_key' => $feature, 'platform' => 'demo',
                        'country' => 'Pakistan', 'city' => 'QA sample',
                        'metadata' => ['qa_demo' => true, 'not_a_real_user_action' => true],
                        'occurred_at' => now()->subDays(max(0, $days - $i)),
                    ]);
                }
            }
        }

        if (Schema::hasTable('satisfaction_surveys')) {
            foreach ($users->take(3)->values() as $i => $user) {
                SatisfactionSurvey::firstOrCreate(
                    ['user_id' => $user->id, 'context' => 'qa_demo_nps', 'platform' => 'demo'],
                    ['score' => [10, 7, 4][$i], 'comment' => 'QA DEMO — synthetic satisfaction response.']
                );
            }
            // Production NPS intentionally excludes qa_demo_nps. For real UI NPS,
            // submit the actual member survey manually; it is never manufactured by this seeder.
        }

        if (Schema::hasTable('notification_delivery_logs')) {
            foreach ($users->take(2)->values() as $i => $user) {
                NotificationDeliveryLog::firstOrCreate(
                    ['user_id' => $user->id, 'channel' => 'email', 'event_key' => 'qa_demo_notification'],
                    ['status' => $i === 0 ? 'sent' : 'failed',
                        'payload' => ['qa_demo' => true, 'label' => 'SYNTHETIC — NOT SENT'],
                        'sent_at' => $i === 0 ? now()->subHour() : null,
                        'failure_reason' => $i === 1 ? 'QA DEMO — mock failure' : null,
                        'deep_link' => '/member/client-scope/notifications',
                        'reminder_count' => 0]
                );
            }
        }

        if (Schema::hasTable('support_tickets')) {
            foreach ([
                ['QA-DEMO-SUPPORT-OPEN', 0, null],
                ['QA-DEMO-SUPPORT-RATED', 1, 5],
            ] as [$code, $status, $rating]) {
                $owner = $users->first();
                $data = ['sender_user_id' => $owner->id,
                    'subject' => 'QA DEMO — support ticket example',
                    'description' => 'Synthetic support request for staging UI walkthrough.',
                    'status' => $status];
                if (Schema::hasColumn('support_tickets', 'preferred_language')) $data['preferred_language'] = 'ur';
                if (Schema::hasColumn('support_tickets', 'expected_response_at')) $data['expected_response_at'] = now()->addHours(24);
                if (Schema::hasColumn('support_tickets', 'rating') && $rating !== null) {
                    $data['rating'] = $rating;
                    $data['rating_comment'] = 'QA DEMO — agent was helpful';
                    $data['rated_at'] = now()->subDay();
                    $data['resolved_at'] = now()->subDay();
                }
                if (! DB::table('support_tickets')->where('ticket_id', $code)->exists()) {
                    DB::table('support_tickets')->insert(['ticket_id' => $code] + $data
                        + ['created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }
}

