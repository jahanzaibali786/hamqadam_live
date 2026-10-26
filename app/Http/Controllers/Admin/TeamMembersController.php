<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HamqadamTeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamMembersController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['active', 'hidden'])],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = (string) ($filters['status'] ?? '');

        $members = HamqadamTeamMember::query()
            ->when($search !== '', function ($query) use ($search) {
                $pattern = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(function ($subQuery) use ($pattern) {
                    $subQuery->where('name', 'like', $pattern)
                        ->orWhere('role', 'like', $pattern)
                        ->orWhere('location', 'like', $pattern)
                        ->orWhere('group', 'like', $pattern);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.website_settings.team_members', [
            'members' => $members,
            'search' => $search,
            'status' => $status,
            'totalMembers' => HamqadamTeamMember::count(),
            'activeMembers' => HamqadamTeamMember::where('is_active', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.website_settings.team_member_form', [
            'member' => null,
        ]);
    }

    public function edit(HamqadamTeamMember $member): View
    {
        return view('admin.website_settings.team_member_form', compact('member'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = (int) HamqadamTeamMember::max('sort_order') + 1;
        }

        HamqadamTeamMember::create($data);

        return redirect()->route('website.team_members.index')
            ->with('success', 'Team member added successfully. You can add another member at any time.');
    }

    public function update(Request $request, HamqadamTeamMember $member): RedirectResponse
    {
        $data = $this->validated($request);
        if (! isset($data['sort_order'])) {
            $data['sort_order'] = $member->sort_order;
        }

        $member->update($data);

        return redirect()->route('website.team_members.index')
            ->with('success', 'Team member updated successfully.');
    }

    public function destroy(HamqadamTeamMember $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('website.team_members.index')
            ->with('success', 'Team member deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'initials' => ['nullable', 'string', 'max:8'],
            'role' => ['required', 'string', 'max:180'],
            'badge' => ['nullable', 'string', 'max:100'],
            'group' => ['required', Rule::in(['All', 'Family', 'Technology', 'Trust'])],
            'bio' => ['nullable', 'string', 'max:1500'],
            'focus' => ['nullable', 'string', 'max:1200'],
            'location' => ['nullable', 'string', 'max:120'],
            'action_text' => ['nullable', 'string', 'max:80'],
            'action_link' => ['nullable', 'string', 'max:512'],
            'image_id' => ['nullable', 'integer', 'exists:uploads,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['action_link'])) {
            $url = $data['action_link'];
            $isInternal = preg_match('#^/(?!/)#', $url) === 1;
            $isHttps = filter_var($url, FILTER_VALIDATE_URL)
                && parse_url($url, PHP_URL_SCHEME) === 'https';

            if (! $isInternal && ! $isHttps) {
                throw ValidationException::withMessages([
                    'action_link' => 'Use a relative /path or a full HTTPS URL.',
                ]);
            }
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
