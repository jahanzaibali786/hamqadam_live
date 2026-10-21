<?php

declare(strict_types=1);

namespace Tests\Feature\Phase10;

use Database\Seeders\HamqadamQaGuard;
use Database\Seeders\DemoMatrimonialSeeder;
use Database\Seeders\HamqadamQaDemoSeeder;
use Tests\TestCase;

final class QaSeederSafetyTest extends TestCase
{
    public function test_production_blocks_all_qa_seeders(): void
    {
        app()->detectEnvironment(fn () => 'production');
        $this->expectException(\RuntimeException::class);
        HamqadamQaGuard::assertSafe();
    }

    public function test_demo_seeder_entrypoints_exist(): void
    {
        $this->assertTrue(class_exists(DemoMatrimonialSeeder::class));
        $this->assertTrue(class_exists(HamqadamQaDemoSeeder::class));
    }

    public function test_qa_seeder_dependencies_exist(): void
    {
        foreach (['Members','Configuration','Activity','Payments','Match','Content'] as $name) {
            $this->assertTrue(class_exists('Database\\Seeders\\HamqadamQa'.$name.'Seeder'));
        }
    }
}
