<?php
declare(strict_types=1);
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Intentionally runnable in production (no environment guard).
 * Published SAMPLE editorial content, idempotently keyed by a unique slug.
 * Existing posts/categories are never overwritten or deleted.
 * Run: php artisan db:seed --class=HamqadamEditorialBlogSeeder --force
 */
final class HamqadamEditorialBlogSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('blogs') || !Schema::hasTable('blog_categories')) {
            $this->command?->warn('Blog tables are missing; run migrations first.');
            return;
        }
        $content = [
            'Relationship Tips' => [
                ['How to Build a Strong Foundation for Marriage', 'Understand shared values, respectful communication, and practical expectations before marriage.'],
                ['Understanding Compatibility Before Marriage', 'Explore the many dimensions of a healthy and compatible relationship.'],
                ['Questions to Ask Before Saying Yes', 'Discuss long-term expectations calmly, honestly and with mutual respect.'],
                ['How to Improve Communication as a Couple', 'Practical habits for listening, managing disagreement and expressing needs.'],
            ],
            'Marriage Guidance' => [
                ['Ten Things to Consider Before Marriage', 'Consider your values, personal goals, communication and expectations.'],
                ['Preparing for the First Family Meeting', 'Practical tips for a respectful first introduction between families.'],
                ['Planning a Meaningful Marriage Journey', 'A thoughtful checklist for decisions, timeframes and mutual understanding.'],
            ],
            'Family & Culture' => [
                ['The Role of Family in a Successful Marriage', 'Explore how supportive family relationships can benefit a new couple.'],
                ['Balancing Personal Choice and Family Traditions', 'Approach traditions and individual needs with understanding.'],
                ['Respectful Family Involvement in Matchmaking', 'Discuss family involvement and boundaries with kindness.'],
            ],
            'Personal Growth' => [
                ['Building Emotional Readiness for Commitment', 'Learn habits that support self-awareness and responsible partnership.'],
                ['Healthy Boundaries in New Relationships', 'Identify and discuss boundaries openly and respectfully.'],
                ['Personal Growth Before a Lifelong Commitment', 'Reflect on your goals, values and communication habits.'],
            ],
            'Islamic Guidance' => [
                ['Marriage and Mutual Respect in Islam', 'A general introduction to kindness, respect and responsibility in marriage.'],
                ['Thoughtful Questions for Nikah Preparation', 'Discuss important values and practical expectations before Nikah.'],
            ],
            'Success Stories' => [
                ['What Strong Relationships Have in Common', 'Explore practical themes shared across successful relationships.'],
                ['Making Time for Meaningful Conversations', 'Use curiosity and attentive listening to build stronger connections.'],
            ],
        ];
        foreach ($content as $categoryName => $articles) {
            $categorySlug = Str::slug($categoryName);
            $category = DB::table('blog_categories')->where('slug',$categorySlug)->first();
            $categoryId = $category?->id ?? DB::table('blog_categories')->insertGetId([
                'category_name'=>$categoryName,'slug'=>$categorySlug,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($articles as [$title,$summary]) {
                $slug = 'hamqadam-guide-'.Str::slug($title);
                if(DB::table('blogs')->where('slug',$slug)->exists()) continue;
                $paragraphs = [
                    $summary,
                    'Every relationship is unique. Begin by understanding the priorities and expectations of everyone involved. Thoughtful conversations about daily habits, family involvement and future plans can help create clarity.',
                    'Give each person room to speak and listen with patience. Respect differences and avoid making assumptions about someone based on a brief profile or first conversation.',
                    'Before taking the next step, discuss practical considerations openly. Make room for questions, seek trusted guidance when useful, and make decisions freely without pressure.',
                    'Hamqadam encourages authentic, respectful connections. This sample editorial article offers general information rather than personalised legal, medical or religious advice.',
                ];
                $html = '<h2>'.e($title).'</h2>'.implode('',array_map(fn($p)=>'<p>'.e($p).'</p>',$paragraphs));
                DB::table('blogs')->insert([
                    'category_id'=>$categoryId,
                    'title'=>$title,
                    'slug'=>$slug,
                    'short_description'=>$summary,
                    'description'=>$html,
                    'meta_title'=>$title.' | Hamqadam',
                    'meta_description'=>$summary,
                    'meta_keywords'=>str_replace(' ', ', ',strtolower($categoryName)).', marriage, relationships, hamqadam',
                    'status'=>1,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        }
        $this->command?->info('Sample Hamqadam editorial articles seeded. Review and replace with approved original editorial content as needed.');
    }
}
