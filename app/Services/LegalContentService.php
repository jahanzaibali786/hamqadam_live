<?php

namespace App\Services;

use App\Models\ManagedContentRevision;
use App\Models\Setting;
use App\Support\RefundPolicyContent;
use App\Support\SafeRichText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LegalContentService
{
    public const EDITORIAL_KEYS = ['privacy', 'terms'];

    public function publishedRevision(string $key): ?ManagedContentRevision
    {
        // A later 'unpublished' marker hides an earlier published revision,
        // while newer drafts never affect what the public sees.
        return ManagedContentRevision::forPage($key)
            ->whereIn('status', ['published', 'unpublished'])
            ->orderByDesc('id')->first();
    }

    public function latestDraft(string $key): ?ManagedContentRevision
    {
        return ManagedContentRevision::forPage($key)
            ->where('status', 'draft')->orderByDesc('id')->first();
    }

    public function publicData(string $key): ?array
    {
        $revision = $this->publishedRevision($key);
        if ($revision) {
            return $revision->status === 'published'
                ? json_decode($revision->content_json, true) : null;
        }
        return $key === 'refund' ? RefundPolicyContent::defaults() : null;
    }

    public function editingData(string $key): ?array
    {
        $draft = $this->latestDraft($key);
        if ($draft) {
            return json_decode($draft->content_json, true);
        }
        if (in_array($key, self::EDITORIAL_KEYS, true)) {
            return \App\Http\Controllers\HamqadamEditorialController::content($key);
        }
        return $this->publicData($key);
    }

    public function normalizePolicy(array $input): array
    {
        $sections = $input['sections'] ?? [];
        if (! is_array($sections) || ! array_is_list($sections) ||
            count($sections) < 1 || count($sections) > 25) {
            throw new \InvalidArgumentException('Provide 1 to 25 policy sections.');
        }
        $result = [];
        foreach ($sections as $section) {
            if (! is_array($section)) {
                throw new \InvalidArgumentException('Invalid policy section.');
            }
            $heading = trim((string) ($section['heading'] ?? ''));
            if ($heading === '' || mb_strlen($heading) > 180) {
                throw new \InvalidArgumentException('Section headings are required (180 characters maximum).');
            }
            $result[] = [
                'heading' => $heading,
                'body' => SafeRichText::clean((string) ($section['body'] ?? '')),
            ];
        }
        foreach (['title' => 170,'meta_title'=>170,'meta_description'=>500,
                  'turnaround_heading'=>180] as $field => $maximum) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value === '' || mb_strlen($value) > $maximum) {
                throw new \InvalidArgumentException("Invalid {$field}.");
            }
            $clean[$field] = $value;
        }
        $clean['sections'] = $result;
        $clean['turnaround_body'] = SafeRichText::clean((string) ($input['turnaround_body'] ?? ''));
        if (trim(strip_tags($clean['turnaround_body'])) === '') {
            throw new \InvalidArgumentException('Refund processing statement cannot be empty.');
        }
        return $clean;
    }

    public function normalizeHelp(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $category = trim((string) ($input['category'] ?? 'General'));
        $description = trim((string) ($input['description'] ?? ''));
        if ($title === '' || mb_strlen($title) > 180 ||
            $category === '' || mb_strlen($category) > 100 ||
            mb_strlen($description) > 500) {
            throw new \InvalidArgumentException('Invalid article title, category or description.');
        }

        return [
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'body' => SafeRichText::clean((string) ($input['body'] ?? '')),
            'is_faq' => (bool) ($input['is_faq'] ?? false),
            'sort_order' => max(0, min(99999, (int) ($input['sort_order'] ?? 0))),
        ];
    }

    public function revisions(string $key)
    {
        return ManagedContentRevision::forPage($key)->with('editor')
            ->orderByDesc('id')->limit(30)->get();
    }

    public function save(string $key, array $data, string $action, int $adminId): ManagedContentRevision
    {
        if (! in_array($action, ['draft', 'publish', 'unpublish'], true)) {
            throw new \InvalidArgumentException('Invalid publishing action.');
        }

        return DB::transaction(function () use ($key,$data,$action,$adminId) {
            $revision = ManagedContentRevision::create([
                'page_key' => $key,
                'status' => $action === 'publish' ? 'published' :
                    ($action === 'unpublish' ? 'unpublished' : 'draft'),
                'content_json' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'editor_id' => $adminId,
                'published_at' => $action !== 'draft' ? now() : null,
            ]);
            if ($action === 'publish' && in_array($key, self::EDITORIAL_KEYS, true)) {
                Setting::updateOrCreate(['type' => 'hq_editorial_' . $key], [
                    'value' => $revision->content_json,
                ]);
                DB::afterCommit(fn () => Cache::forget('settings'));
            }
            return $revision;
        });
    }

    public function availableHelp(): array
    {
        $revisions = ManagedContentRevision::query()
            ->where('page_key', 'like', 'help:%')
            ->whereIn('status', ['published','unpublished'])
            ->orderByDesc('id')->get();

        $seen = [];
        $articles = [];
        foreach ($revisions as $revision) {
            $key = $revision->page_key;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            if ($revision->status !== 'published') {
                continue;
            }
            $data = json_decode($revision->content_json, true);
            if (! is_array($data)) {
                continue;
            }
            $articles[] = [
                'slug' => Str::after($key, 'help:'),
                'data' => $data,
            ];
        }

        usort($articles, function ($a, $b) {
            return ($a['data']['sort_order'] ?? 0) <=> ($b['data']['sort_order'] ?? 0)
                ?: strcmp($a['data']['title'], $b['data']['title']);
        });
        return $articles;
    }
}
