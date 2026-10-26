<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class QaWalletMatchReferenceLayoutTest extends TestCase
{
    public function test_wallet_reference_uses_actual_prices_and_payment_records(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/member/wallet/index.blade.php'));
        $this->assertStringContainsString('CustomCoinService::amountFor($amount)', $blade);
        $this->assertStringContainsString('$package_payments', $blade);
        $this->assertStringContainsString('route(\'custom_coins.form\')', $blade);
        $this->assertStringContainsString('Payment Methods', $blade);
        $this->assertStringContainsString('Recent Transactions', $blade);
        $this->assertStringNotContainsString('Rs 499', $blade);
    }

    public function test_top_match_does_not_show_a_second_member_list(): void
    {
        $blade = file_get_contents(resource_path('views/client_scope/portal.blade.php'));
        $this->assertStringContainsString('$topMatch=$matches->first()', $blade);
        $this->assertStringContainsString('score_breakdown', $blade);
        $this->assertStringContainsString('compatibility_reasons', $blade);
        $this->assertStringContainsString('route(\'my_matched_profiles\')', $blade);
        $this->assertStringNotContainsString('@foreach($matches', $blade);
    }

    public function test_sidebar_has_visible_links_not_a_more_features_accordion(): void
    {
        $blade = file_get_contents(resource_path('views/frontend/member/sidebar.blade.php'));
        $this->assertStringContainsString("['client.scope.portal','Why This Match'", $blade);
        $this->assertStringContainsString('@foreach($extraMenu as $navItem)', $blade);
        $this->assertStringNotContainsString('<details class="hm-member-extras"', $blade);
    }
}
