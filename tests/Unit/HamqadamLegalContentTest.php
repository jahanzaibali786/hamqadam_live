<?php

namespace Tests\Unit;

use App\Support\RefundPolicyContent;
use App\Support\SafeRichText;
use PHPUnit\Framework\TestCase;

class HamqadamLegalContentTest extends TestCase
{
    public function test_approved_refund_policy_contains_every_section(): void
    {
        $policy = RefundPolicyContent::defaults();
        $titles = array_column($policy['sections'], 'heading');
        $this->assertSame([
            'Introduction',
            'Subscription Payments',
            'Refund Eligibility',
            'Non-Refundable Situations',
            'Cancellation Policy',
            'Returns and Exchanges',
            'How to Request a Refund',
            'Refund Processing',
            'Policy Updates',
        ], $titles);
        $this->assertSame('REFUND PROCESSING TIME: 7 WORKING DAYS', $policy['turnaround_heading']);
        $this->assertStringContainsString('within 7 working days', $policy['turnaround_body']);
        $this->assertStringContainsString('original payment method', $policy['turnaround_body']);
        $this->assertStringContainsString('/refund-policy',
            parse_url('https://hamqadam.com/refund-policy', PHP_URL_PATH));
    }

    public function test_rich_text_removes_scripts_events_and_unsafe_links(): void
    {
        if (! class_exists(\DOMDocument::class)) {
            $this->markTestSkipped('ext-dom is required for SafeRichText.');
        }

        $html = SafeRichText::clean(
            '<p onclick="alert(1)">Safe text<script>alert(1)</script></p>' .
            '<a href="javascript:alert(1)" onmouseover="alert(1)">Bad</a>' .
            '<a href="https://hamqadam.com/refund-policy">Safe link</a>'
        );
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('onmouseover', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('href="https://hamqadam.com/refund-policy"', $html);
    }
}
