<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Optional unpublished editorial fixtures; content must be approved before going public. */
final class HamqadamQaContentSeeder extends Seeder
{
    public function run(): void
    {
        HamqadamQaGuard::assertSafe();

        if (Schema::hasTable('blog_categories') && Schema::hasTable('blogs')) {
            $slug = 'qa-demo-relationship-guidance';
            $category = DB::table('blog_categories')->where('slug', $slug)->first();
            $categoryId = $category?->id ?: DB::table('blog_categories')->insertGetId([
                'category_name' => 'QA DEMO — Relationship Guidance',
                'slug' => $slug, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ([
                ['qa-demo-communicating-with-respect', 'QA DEMO — Communicating With Respect',
                    'A sample editorial article to verify category, preview and publishing workflows.'],
                ['qa-demo-setting-family-expectations', 'QA DEMO — Discussing Family Expectations',
                    'A sample editorial article. Replace with client-approved content and imagery.'],
            ] as [$postSlug, $title, $description]) {
                if (! DB::table('blogs')->where('slug', $postSlug)->exists()) {
                    DB::table('blogs')->insert([
                        'category_id' => $categoryId, 'title' => $title,
                        'slug' => $postSlug, 'short_description' => $description,
                        'description' => '<p>QA DEMO ONLY — example content for staging. This is not approved advice.</p>',
                        'status' => 0, // draft/unpublished; no accidental public blog entries
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        if (Schema::hasTable('happy_stories')) {
            $userId = User::query()->where('email','demo.user@hamqadam.test')->value('id');
            if ($userId && ! DB::table('happy_stories')->where('title','QA DEMO — Unpublished Success Story')->exists()) {
                DB::table('happy_stories')->insert([
                    'user_id' => $userId,
                    'title' => 'QA DEMO — Unpublished Success Story',
                    'partner_name' => 'QA DEMO — Synthetic Partner',
                    'details' => 'Synthetic success story for moderator review. NOT an actual relationship or marriage.',
                    'photos' => '', 'approved' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }
}

