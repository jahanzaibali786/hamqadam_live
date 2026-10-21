<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaHomeNavigationAndAdminMenuTest extends TestCase {
    public function test_registration_initial_render_does_not_auto_scroll(): void {
        $js = file_get_contents(public_path('assets/js/registration-web-stepwise.js'));
        $this->assertStringContainsString('showStep(1, false)', $js);
        $this->assertStringContainsString('if (scrollToForm)', $js);
    }
    public function test_notification_sql_avoids_reserved_reads_alias(): void {
        $php = file_get_contents(app_path('Services/ClientScope/NotificationEngagementReportService.php'));
        $this->assertStringContainsString('as read_events', $php);
        $this->assertStringContainsString("'reads'=>(int)\$r->read_events", $php);
        $this->assertStringNotContainsString('as reads', $php);
    }
    public function test_key_admin_routes_are_not_duplicated(): void {
        $blade = file_get_contents(resource_path('views/admin/inc/sidenav.blade.php'));
        foreach (['admin.payment_refunds.index','admin.payment_audit.index','payment-coupons.index','happy-story.index','admin.help-chat.index','support-tickets.index','admin.notification_audit.index','set_referral_commission','referals.users','blog.index'] as $name) {
            $this->assertSame(1, substr_count($blade, "route('{$name}'"), "Duplicate admin menu route: {$name}");
        }
    }
}
