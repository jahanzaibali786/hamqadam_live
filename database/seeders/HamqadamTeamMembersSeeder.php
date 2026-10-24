<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HamqadamTeamMembersSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('hamqadam_team_members')) return;
        // Seed only a fresh table: reruns must not recreate deliberately removed members
        // or overwrite edits made in CMS.
        if (DB::table('hamqadam_team_members')->exists()) return;
        $defaults = json_decode(file_get_contents(resource_path('data/hamqadam_editorial_pages.json')), true);
        $members = $defaults['team']['practice']['members'] ?? [];
        foreach ($members as $index => $item) {
            DB::table('hamqadam_team_members')->insert([
                'name' => $item['name'], 'initials' => $item['initials'] ?? '',
                'role' => $item['role'] ?? '', 'badge' => $item['badge'] ?? '',
                'group' => $item['group'] ?? 'Family',
                'bio' => $item['bio'] ?? '', 'focus' => $item['focus'] ?? '',
                'location' => $item['location'] ?? '',
                'action_text' => $item['action_text'] ?? 'Contact',
                'action_link' => $item['action_link'] ?? '/our-team',
                'sort_order' => $index + 1, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
