<?php

namespace Tests\Feature\Phase10;

use Tests\TestCase;

class MemberProfileBlogsWalletMatchedFixTest extends TestCase
{
    public function test_matched_profiles_directives_are_standalone_and_balanced(): void
    {
        $blade=file_get_contents(resource_path('views/frontend/member/matched_profiles.blade.php'));
        $this->assertSame(1, substr_count($blade, '@forelse'));
        $this->assertSame(1, substr_count($blade, '@endforelse'));
        $this->assertSame(2, substr_count($blade, '@if ('));
        $this->assertSame(3, substr_count($blade, '@endif'));
        $this->assertStringNotContainsString('@endif@if', $blade);
        $this->assertStringContainsString('frontend.member.partials.match_actions', $blade);
    }

    public function test_member_profile_blog_and_wallet_styles_use_wine_palette(): void
    {
        $profile=file_get_contents(public_path('assets/css/hamqadam-public-detail.css'));
        $blog=file_get_contents(public_path('assets/css/hamqadam-unified-header-blog.css'));
        $wallet=file_get_contents(public_path('assets/css/hamqadam-wallet-match-2026.css'));
        $palette=file_get_contents(public_path('assets/css/hamqadam-member-brand-palette-20261026.css'));
        $this->assertStringNotContainsString('#ef2478', $profile);
        $this->assertStringNotContainsString('#ed2478', $blog);
        $this->assertStringContainsString('linear-gradient(135deg,#9f1239,#67051f)', $wallet);
        $this->assertStringContainsString('.hm-wallet-choices .hm-wallet-buy', $palette);
        $this->assertStringContainsString('color:#fff!important', $palette);
    }
}
