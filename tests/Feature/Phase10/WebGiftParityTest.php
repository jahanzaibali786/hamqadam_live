<?php
namespace Tests\Feature\Phase10;
use Tests\TestCase;
class WebGiftParityTest extends TestCase
{
    public function test_web_uses_existing_mobile_gift_service_and_catalog(): void
    {
        $controller=file_get_contents(app_path('Http/Controllers/MemberGiftController.php'));
        $service=file_get_contents(app_path('Services/Api/V1/Gift/GiftService.php'));
        $routes=file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString('GiftService $service',$controller);
        $this->assertStringContainsString('confirmed_coins',$controller);
        $this->assertStringContainsString('DB::transaction',$service);
        $this->assertStringContainsString("lockForUpdate()",$service);
        $this->assertStringContainsString("floor($gift->coins * 0.7)",$service);
        $this->assertStringContainsString('gift_price_changed',$service);
        $this->assertStringContainsString("name('member.gifts.send')",$routes);
        $this->assertStringContainsString("name('member.gifts.transaction')",$routes);
    }
    public function test_admin_catalog_pricing_and_member_card_entry_are_present(): void
    {
        $catalog=file_get_contents(app_path('Http/Controllers/Admin/GiftCatalogWebController.php'));
        $api=file_get_contents(app_path('Http/Controllers/Api/V1/Gift/GiftAdminController.php'));
        $menu=file_get_contents(resource_path('views/frontend/member/partials/match_actions.blade.php'));
        $this->assertStringContainsString("name('admin.gifts.index')",file_get_contents(base_path('routes/admin.php')));
        $this->assertStringContainsString('authorizeAdmin($request)',$catalog);
        $this->assertStringContainsString('$this->authorizeAdmin()',$api);
        $this->assertStringContainsString('member.gifts.compose',$menu);
        $this->assertStringContainsString('Gift::create',$catalog);
    }
    public function test_gift_web_views_use_safe_fallback_icon_for_flutter_only_assets(): void
    {
        $compose=file_get_contents(resource_path('views/frontend/member/gifts/compose.blade.php'));
        $this->assertStringContainsString('las la-gift',$compose);
        $this->assertStringContainsString('confirmed_coins',$compose);
        $this->assertStringContainsString('Math.floor(price*.7)',$compose);
        $history=file_get_contents(resource_path('views/frontend/member/gifts/index.blade.php'));
        $this->assertStringContainsString('Gifts Received',$history);
        $this->assertStringContainsString('Gifts Sent',$history);
    }
}
