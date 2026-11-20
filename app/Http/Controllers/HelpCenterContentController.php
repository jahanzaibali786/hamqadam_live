<?php

namespace App\Http\Controllers;

use App\Models\ManagedContentRevision;
use App\Services\LegalContentService;
use App\Support\SafeRichText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class HelpCenterContentController extends Controller
{
    public function index(LegalContentService $cms)
    {
        $articles = $cms->availableHelp();
        $categories = $this->categories($cms);
        return view('frontend.legal.help_index', compact('articles','categories'));
    }

    public function article(string $slug, LegalContentService $cms)
    {
        $article = $cms->publicData('help:' . $slug);
        abort_unless($article, 404);
        return view('frontend.legal.help_article', compact('article','slug'));
    }

    private function categories(LegalContentService $cms): array
    {
        $data = $cms->publicData('help-categories');
        return is_array($data) && is_array($data['items'] ?? null)
            ? $data['items'] : ['General', 'Account', 'Subscriptions & Payments', 'Safety'];
    }

    public function manage(LegalContentService $cms)
    {
        return view('admin.website_settings.legal.help_index', [
            'articles' => $cms->availableHelp(),
            'drafts' => ManagedContentRevision::query()->where('page_key','like','help:%')
                ->where('status','draft')->orderByDesc('id')->get()->unique('page_key'),
            'categories' => $this->categories($cms),
        ]);
    }

    public function edit(string $slug, LegalContentService $cms)
    {
        abort_unless(preg_match('/^[a-z0-9-]{1,100}$/', $slug), 404);
        $data = $cms->editingData('help:' . $slug) ?? [
            'title' => '', 'category' => 'General',
            'description' => '', 'body' => '', 'is_faq' => false, 'sort_order' => 0,
        ];
        $revisions = $cms->revisions('help:' . $slug);
        $categories = $this->categories($cms);
        return view('admin.website_settings.legal.help_edit', compact(
            'slug','data','revisions','categories'
        ));
    }

    public function save(Request $request, LegalContentService $cms)
    {
        $validated = $request->validate([
            'slug' => ['required','regex:/^[a-z0-9-]{1,100}$/'],
            'action' => ['required','in:draft,publish,unpublish'],
            'title' => ['required','string','max:180'],
            'category' => ['required','string','max:100', \Illuminate\Validation\Rule::in($this->categories($cms))],
            'description' => ['nullable','string','max:500'],
            'body' => ['required','string','max:60000'],
            'is_faq' => ['nullable','boolean'],
            'sort_order' => ['nullable','integer','between:0,99999'],
        ]);
        $slug = $validated['slug'];
        try {
            $data = $cms->normalizeHelp($validated);
            $cms->save('help:' . $slug, $data, $validated['action'], auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['body' => $e->getMessage()]);
        }
        return redirect()->route('website.help.edit', $slug)
            ->with('success', 'Article ' . ($validated['action'] === 'draft'
                ? 'saved as draft' : ($validated['action'] === 'unpublish' ? 'unpublished' : 'published')) . '.');
    }

    public function preview(ManagedContentRevision $revision)
    {
        abort_unless(str_starts_with($revision->page_key, 'help:'), 404);
        $article = json_decode($revision->content_json, true);
        $slug = Str::after($revision->page_key, 'help:');
        return response()->view('frontend.legal.help_article', [
            'article' => $article, 'slug' => $slug, 'preview' => true,
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function restore(Request $request, ManagedContentRevision $revision, LegalContentService $cms)
    {
        abort_unless(str_starts_with($revision->page_key, 'help:') ||
            $revision->page_key === 'help-categories', 404);
        abort_unless($revision->status === 'published', 422);
        $cms->save($revision->page_key, json_decode($revision->content_json,true),
            'publish', auth()->id());
        $route = $revision->page_key === 'help-categories'
            ? 'website.help.categories' : 'website.help.edit';
        $params = $revision->page_key === 'help-categories'
            ? [] : [Str::after($revision->page_key, 'help:')];
        return redirect()->route($route, $params)
            ->with('success', 'Previously published content restored as a new revision.');
    }

    public function editCategories(LegalContentService $cms)
    {
        $data = $cms->editingData('help-categories');
        $items = $data['items'] ?? $this->categories($cms);
        $revisions = $cms->revisions('help-categories');
        return view('admin.website_settings.legal.help_categories', compact('items','revisions'));
    }

    public function saveCategories(Request $request, LegalContentService $cms)
    {
        $validated = $request->validate([
            'action' => ['required','in:draft,publish'],
            'categories' => ['required','string','max:4000'],
        ]);
        $items = array_values(array_unique(array_filter(array_map(
            'trim', preg_split('/\r\n|\r|\n/', $validated['categories'])
        ))));
        if (count($items) < 1 || count($items) > 40 ||
            collect($items)->contains(fn ($item) => mb_strlen($item) > 100)) {
            return back()->withErrors(['categories' => 'Use 1–40 categories, each at most 100 characters.']);
        }
        $cms->save('help-categories', ['items'=>$items], $validated['action'], auth()->id());
        return back()->with('success', 'Help categories ' .
            ($validated['action'] === 'draft' ? 'saved as draft.' : 'published.'));
    }
}
