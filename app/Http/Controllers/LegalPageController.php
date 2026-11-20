<?php

namespace App\Http\Controllers;

use App\Models\ManagedContentRevision;
use App\Services\LegalContentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class LegalPageController extends Controller
{
    public function refund(LegalContentService $cms): View
    {
        $data = $cms->publicData('refund');
        abort_unless($data, 404);
        return view('frontend.legal.refund', compact('data'));
    }

    public function edit(LegalContentService $cms): View
    {
        $data = $cms->editingData('refund');
        $revisions = $cms->revisions('refund');
        $draft = $cms->latestDraft('refund');
        $lastPublished = $cms->publishedRevision('refund');

        return view('admin.website_settings.legal.refund', compact(
            'data', 'revisions', 'draft', 'lastPublished'
        ));
    }

    public function save(Request $request, LegalContentService $cms)
    {
        $request->validate([
            'action' => ['required','in:draft,publish'],
            'title' => ['required','string','max:170'],
            'meta_title' => ['required','string','max:170'],
            'meta_description' => ['required','string','max:500'],
            'sections' => ['required','array','min:1','max:25'],
            'sections.*.heading' => ['required','string','max:180'],
            'sections.*.body' => ['nullable','string','max:60000'],
            'turnaround_heading' => ['required','string','max:180'],
            'turnaround_body' => ['required','string','max:60000'],
        ]);

        try {
            $data = $cms->normalizePolicy($request->all());
            $revision = $cms->save('refund', $data, $request->input('action'), auth()->id());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['content' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('website.legal.refund.edit')
            ->with('success', $revision->status === 'draft'
                ? 'Draft saved; live refund policy remains unchanged.'
                : 'Refund Policy published successfully without deployment.');
    }

    public function preview(ManagedContentRevision $revision)
    {
        abort_unless($revision->page_key === 'refund', 404);
        $data = json_decode($revision->content_json, true);
        return response()->view('frontend.legal.refund', [
            'data' => $data,
            'preview' => true,
        ])->header('X-Robots-Tag','noindex, nofollow');
    }

    public function restore(Request $request, string $page, ManagedContentRevision $revision,
                            LegalContentService $cms)
    {
        abort_unless(in_array($page, ['refund','privacy','terms'], true), 404);
        abort_unless($revision->page_key === $page, 404);
        abort_unless($revision->status === 'published', 422);

        $content = json_decode($revision->content_json, true);
        $cms->save($page, $content, 'publish', auth()->id());
        return redirect()->route($page === 'refund'
            ? 'website.legal.refund.edit' : 'website.editorial.edit', $page === 'refund'
            ? [] : ['page' => $page])
            ->with('success', 'Previous published version restored as a new revision.');
    }
}
