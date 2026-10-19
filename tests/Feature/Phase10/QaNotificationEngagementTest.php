<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use App\Services\ClientScope\NotificationEngagementReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Missing third-party delivery receipts must not be converted into confirmed deliveries. */
final class QaNotificationEngagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::create('notification_delivery_logs', function (Blueprint $t): void {
            $t->id(); $t->string('channel'); $t->string('event_key')->nullable();
            $t->string('status'); $t->timestamp('sent_at')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamp('clicked_at')->nullable();
            $t->unsignedInteger('reminder_count')->default(0);
            $t->timestamps();
        });
        DB::table('notification_delivery_logs')->insert([
            ['channel'=>'email','event_key'=>'qa_communication','status'=>'sent',
                'sent_at'=>now()->subHours(3), 'delivered_at'=>null,
                'read_at'=>now()->subHours(2), 'clicked_at'=>null,
                'reminder_count'=>1,'created_at'=>now()->subHours(3)],
            ['channel'=>'email','event_key'=>'qa_communication','status'=>'failed',
                'sent_at'=>null, 'delivered_at'=>null,
                'read_at'=>null, 'clicked_at'=>null,
                'reminder_count'=>0,'created_at'=>now()->subHours(2)],
        ]);
    }

    public function test_engagement_report_separates_sent_read_from_confirmed_delivery(): void
    {
        $result = app(NotificationEngagementReportService::class)->summarize(30);
        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]['attempts']);
        $this->assertSame(1, $result[0]['sent']);
        $this->assertSame(0, $result[0]['delivered']);
        $this->assertSame(1, $result[0]['reads']);
        $this->assertSame(1, $result[0]['failures']);
        $this->assertEquals(100.0, $result[0]['read_rate']);
    }

    public function test_reporting_window_excludes_old_delivery_events(): void
    {
        DB::table('notification_delivery_logs')->insert([
            'channel'=>'email','event_key'=>'qa_communication','status'=>'sent',
            'sent_at'=>now()->subDays(99), 'created_at'=>now()->subDays(99),
        ]);
        $result = app(NotificationEngagementReportService::class)->summarize(30);
        $this->assertSame(2, $result[0]['attempts']);
    }
}

