<?php
declare(strict_types=1);
namespace Tests\Feature\Phase10;
use Tests\TestCase;
final class QaMemberRemainingScreenDesignTest extends TestCase {
 public function test_reference_views_keep_existing_transaction_and_request_routes():void {
  $checks=[
   'wallet/index.blade.php'=>['custom_coins.form','packages','package_payment.invoice'],
   'referrals/index.blade.php'=>['copy-referral-url','referral-share-url'],
   'messages/index.blade.php'=>['chat_view','chat_refresh','chat.voice_reply'],
   'my_interests.blade.php'=>['member_profile','reject_interest'],
   'interest_requests.blade.php'=>['accept_interest','reject_interest'],
   'notifications.blade.php'=>['notification_view','notification.mark_all_as_read'],
   'member_verifiction_form.blade.php'=>['member.verification_info.store'],
  ];
  foreach($checks as $path=>$markers){
    $source=file_get_contents(resource_path('views/frontend/member/'.$path));
    foreach($markers as $marker)$this->assertStringContainsString($marker,$source,"Missing preserved action {$marker} in {$path}");
  }
 }
 public function test_reference_screen_css_loads_in_member_layout():void {
   $blade=file_get_contents(resource_path('views/frontend/layouts/member_panel.blade.php'));
   $this->assertStringContainsString('hamqadam-member-reference-finish.css',$blade);
   $this->assertStringContainsString('hamqadam-member-workspace-scroll.css',$blade);
   $this->assertFileExists(public_path('assets/css/hamqadam-member-reference-finish.css'));
 }
}
