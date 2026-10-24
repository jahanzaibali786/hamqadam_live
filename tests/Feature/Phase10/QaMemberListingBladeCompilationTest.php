<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class QaMemberListingBladeCompilationTest extends TestCase
{
    public function test_both_member_listing_contexts_and_shared_partials_compile_to_valid_php(): void
    {
        $views = [
            'frontend.member.member_listing.index',
            'frontend.member.member_listing.public_index',
            'frontend.member.member_listing.partials.content',
            'frontend.member.member_listing.partials.modals',
            'frontend.member.member_listing.partials.scripts',
        ];

        foreach ($views as $view) {
            $sourceFile = resource_path('views/' . str_replace('.', '/', $view) . '.blade.php');
            $this->assertFileExists($sourceFile);
            $source = file_get_contents($sourceFile);
            $this->assertNotFalse($source);

            $compiled = Blade::compileString($source);
            $temp = tempnam(sys_get_temp_dir(), 'hamqadam-member-blade-');
            $this->assertNotFalse($temp);

            try {
                $this->assertNotFalse(file_put_contents($temp, $compiled));
                $output = [];
                $status = 1;
                exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($temp) . ' 2>&1', $output, $status);
                $this->assertSame(0, $status, $view . ': ' . implode("\n", $output));
            } finally {
                @unlink($temp);
            }
        }
    }

    public function test_profile_card_source_has_balanced_blade_conditions_and_loops(): void
    {
        $content = file_get_contents(resource_path('views/frontend/member/member_listing/partials/content.blade.php'));
        $this->assertNotFalse($content);
        $this->assertSame(
            preg_match_all('/@if\b/', $content),
            preg_match_all('/@endif\b/', $content)
        );
        $this->assertSame(
            preg_match_all('/@foreach\b/', $content),
            preg_match_all('/@endforeach\b/', $content)
        );
        $this->assertStringContainsString('foreach ($users as $key => $user) {', $content);
    }
}
