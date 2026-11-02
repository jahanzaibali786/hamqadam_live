<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MemberMatchActionController extends Controller
{
    /**
     * Open the existing consent-based workflow for saved and live AI matches.
     * A GET request never creates an interest or proposal.
     */
    public function showProposal(Request $request, int $member)
    {
        $person = User::query()
            ->whereKey($member)
            ->where('user_type', 'member')
            ->where('approved', 1)
            ->where('blocked', 0)
            ->where('deactivated', 0)
            ->whereHas('member', fn ($query) => $query
                ->where(fn ($m) => $m->where('hide_profile', 0)->orWhereNull('hide_profile')))
            ->whereDoesntHave('profile_privacy_setting', fn ($query) => $query->where('invisible_mode', true))
            ->firstOrFail();

        abort_if((int) $person->id === (int) $request->user()->id, 404);
        abort_if(\App\Models\IgnoredUser::where(function ($query) use ($request, $member) {
            $query->where(fn ($q) => $q->where('ignored_by', $request->user()->id)->where('user_id', $member))
                ->orWhere(fn ($q) => $q->where('ignored_by', $member)->where('user_id', $request->user()->id));
        })->exists(), 404);
        abort_if(\App\Models\HiddenProfileUser::where('user_id', $member)
            ->where('hidden_from_user_id', $request->user()->id)->exists(), 404);

        return redirect()->route('member.workflow.show', $member);
    }

    /** Preserve the old POST entry point without bypassing member review. */
    public function propose(Request $request, int $member)
    {
        return $this->showProposal($request, $member);
    }
}
