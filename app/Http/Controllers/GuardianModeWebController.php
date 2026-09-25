<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\GuardianPermission;
use App\Models\ExpressInterest;
use App\Models\FamilyGuardianLink;
use App\Models\FamilyIntroduction;
use App\Models\GuardianActivityLog;
use App\Models\GuardianInvitation;
use App\Models\GuardianPermission as GuardianPermissionModel;
use App\Services\Api\V1\Family\GuardianAuthService;
use App\Services\Api\V1\Family\GuardianModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Website (Blade) Guardian Mode pages for the Primary User: manage guardians,
 * invitations, granular permissions, pause/resume/revoke, the audit trail and
 * family introductions. Reuses the same services the V1 API uses so website
 * and app can never disagree about authorization.
 */
class GuardianModeWebController extends Controller
{
    public function __construct(
        private readonly GuardianModeService $guardianMode,
        private readonly GuardianAuthService $guardianAuth,
    ) {
    }

    /** GET /guardian-mode — main management page. */
    public function index(Request $request)
    {
        $user = Auth::user();

        $links = FamilyGuardianLink::with(['guardian.member'])
            ->where('profile_user_id', $user->id)
            ->latest()
            ->get();

        $invitations = GuardianInvitation::where('profile_user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('frontend.member.guardian_mode.index', [
            'guardianModeEnabled' => (bool) ($user->member?->wali_mode_enabled ?? false),
            'links' => $links,
            'invitations' => $invitations,
            'permissionCatalog' => GuardianPermission::catalog(),
            'presets' => [
                'view_only' => GuardianPermission::preset('view_only'),
                'review' => GuardianPermission::preset('review'),
                'participate' => GuardianPermission::preset('participate'),
            ],
        ]);
    }

    /** POST /guardian-mode/toggle — activate/deactivate Guardian Mode. */
    public function toggle(Request $request)
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        $member = Auth::user()->member()->firstOrFail();
        $member->forceFill(['wali_mode_enabled' => (bool) $request->boolean('enabled')])->save();

        flash(translate('Guardian Mode updated.'))->success();

        return back();
    }

    /** POST /guardian-mode/invitations — create an invitation. */
    public function storeInvitation(Request $request)
    {
        $request->validate([
            'contact' => ['required', 'string', 'max:120'],
            'relationship' => ['required', 'string', 'max:60'],
            'guardian_role' => ['nullable', 'string', 'max:40', 'in:primary,supporting,custom'],
            'permission_preset' => ['required', 'string', 'in:view_only,review,participate,custom'],
            'permissions' => ['nullable', 'array'],
            'is_wali' => ['nullable', 'boolean'],
        ]);

        try {
            $invitation = $this->guardianMode->invite(Auth::user(), $request->all());
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back()->withInput();
        }

        flash(translate('Guardian invitation created. Share this acceptance code with your guardian: ') . $invitation->token)->success();

        return back();
    }

    /** POST /guardian-mode/invitations/{id}/revoke — withdraw a pending invite. */
    public function revokeInvitation(Request $request, int $invitation)
    {
        GuardianInvitation::where('profile_user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($invitation)
            ->forceFill(['status' => 'revoked'])
            ->save();

        flash(translate('Invitation revoked.'))->success();

        return back();
    }

    /** POST /guardian-mode/guardians/{link}/pause|resume|revoke — lifecycle. */
    public function lifecycle(Request $request, int $link, string $action)
    {
        $map = ['pause' => 'pause', 'resume' => 'resume', 'revoke' => 'revoke'];

        if (! isset($map[$action])) {
            abort(404);
        }

        try {
            $this->guardianMode->{$map[$action]}(Auth::user(), $link);
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back();
        }

        flash(translate('Guardian ' . $action . ' successful.'))->success();

        return back();
    }

    /** GET /guardian-mode/guardians/{link}/permissions — granular editor. */
    public function editPermissions(Request $request, int $link)
    {
        $guardianLink = FamilyGuardianLink::where('profile_user_id', Auth::id())
            ->with('guardian')
            ->findOrFail($link);

        $rows = DB::table('guardian_permissions')
            ->where('guardian_link_id', $guardianLink->id)
            ->pluck('is_allowed', 'permission_key');

        return view('frontend.member.guardian_mode.permissions', [
            'link' => $guardianLink,
            'permissionCatalog' => GuardianPermission::catalog(),
            'current' => $rows,
            'presets' => [
                'view_only' => GuardianPermission::preset('view_only'),
                'review' => GuardianPermission::preset('review'),
                'participate' => GuardianPermission::preset('participate'),
            ],
        ]);
    }

    /** POST /guardian-mode/guardians/{link}/permissions — save granular keys. */
    public function updatePermissions(Request $request, int $link)
    {
        $request->validate(['permissions' => ['required', 'array']]);

        $guardianLink = FamilyGuardianLink::where('profile_user_id', Auth::id())->findOrFail($link);

        $this->guardianAuth->syncPermissions($guardianLink, $request->input('permissions'), (int) Auth::id());

        GuardianActivityLog::record($guardianLink->id, $guardianLink->guardian_user_id, (int) Auth::id(), 'guardian_permissions_changed', FamilyGuardianLink::class, $guardianLink->id);

        flash(translate('Guardian permissions updated.'))->success();

        return redirect()->route('guardian_mode.index');
    }

    /** GET /guardian-mode/activity — readable audit trail (§20). */
    public function activity(Request $request)
    {
        $logs = GuardianActivityLog::with('guardian')
            ->where('profile_user_id', Auth::id())
            ->latest()
            ->paginate(30);

        return view('frontend.member.guardian_mode.activity', ['logs' => $logs]);
    }

    /** GET /guardian-mode/introductions — family introduction list. */
    public function introductions(Request $request)
    {
        $user = Auth::user();

        $introductions = FamilyIntroduction::with('proposal')
            ->whereHas('proposal', function ($query) use ($user): void {
                $query->where('user_id', $user->id)->orWhere('interested_by', $user->id);
            })
            ->orWhere('initiated_by', $user->id)
            ->latest()
            ->paginate(20);

        return view('frontend.member.guardian_mode.introductions', ['introductions' => $introductions]);
    }

    /** POST /guardian-mode/introductions — request an introduction on an accepted proposal. */
    public function storeIntroduction(Request $request)
    {
        $request->validate([
            'proposal_id' => ['required', 'integer'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->guardianMode->requestIntroduction(Auth::user(), (int) $request->integer('proposal_id'), $request->input('message'));
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back();
        }

        flash(translate('Family introduction requested. Both families will be notified.'))->success();

        return back();
    }

    /** POST /guardian-mode/introductions/{id}/respond — accept/decline. */
    public function respondIntroduction(Request $request, int $introduction)
    {
        $request->validate(['accept' => ['required', 'boolean']]);

        try {
            $this->guardianMode->respondIntroduction(Auth::user(), $introduction, (bool) $request->boolean('accept'));
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back();
        }

        flash(translate('Family introduction response saved.'))->success();

        return back();
    }

    /** GET /guardian-panel — the Guardian's own dashboard (§9). */
    public function guardianPanel(Request $request)
    {
        $user = Auth::user();

        $managed = FamilyGuardianLink::with(['profile.member', 'profile'])
            ->where('guardian_user_id', $user->id)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->get()
            ->filter(fn (FamilyGuardianLink $link) => $link->paused_at === null);

        $activeIds = $managed->pluck('profile_user_id');

        return view('frontend.member.guardian_mode.guardian_panel', [
            'managed' => $managed,
            'pendingApprovals' => \App\Models\FamilyApprovalRequest::where('guardian_user_id', $user->id)
                ->whereIn('profile_user_id', $activeIds)
                ->where('status', 'pending')
                ->count(),
            'recentActivity' => GuardianActivityLog::with('profile')
                ->whereIn('profile_user_id', $activeIds)
                ->latest()
                ->limit(10)
                ->get(),
            'permissionCatalog' => GuardianPermission::catalog(),
            'guardianAuth' => $this->guardianAuth,
        ]);
    }

    /** POST /guardian-panel/feedback — guardian match actions from the website. */
    public function guardianFeedbackAction(Request $request)
    {
        $request->validate([
            'profile_user_id' => ['required', 'integer'],
            'target_user_id' => ['required', 'integer'],
            'action' => ['required', 'string', 'in:shortlist,not_suitable,recommend,note'],
            'reason' => ['nullable', 'string', 'max:60'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['nullable', 'string', 'in:guardian_only,primary_and_guardian,family_context'],
        ]);

        $profileUserId = (int) $request->integer('profile_user_id');
        $targetUserId = (int) $request->integer('target_user_id');

        try {
            match ($request->input('action')) {
                'shortlist' => $this->guardianMode->shortlistMatch(Auth::user(), $profileUserId, $targetUserId),
                'note' => $this->guardianMode->note(Auth::user(), $profileUserId, $targetUserId, (string) $request->input('comment', ''), (string) $request->input('visibility', 'primary_and_guardian')),
                default => $this->guardianMode->feedback(Auth::user(), $profileUserId, $targetUserId, $request->input('action'), $request->input('reason'), $request->input('comment')),
            };
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();

            return back();
        }

        flash(translate('Action recorded.'))->success();

        return back();
    }

    /** GET /guardian-panel/matches — match review feed for one managed profile (§10). */
    public function guardianMatches(Request $request)
    {
        $request->validate(['profile_user_id' => ['required', 'integer']]);

        $profileUserId = (int) $request->integer('profile_user_id');
        $user = Auth::user();

        $link = FamilyGuardianLink::where('guardian_user_id', $user->id)
            ->where('profile_user_id', $profileUserId)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->firstOrFail();

        abort_if($link->paused_at !== null, 403, 'Guardian access is paused.');

        $matches = \App\Models\ProfileMatch::with('matchedUser.member')
            ->where('user_id', $profileUserId)
            ->orderByDesc('match_percentage')
            ->limit(20)
            ->get();

        $feedback = GuardianPermissionModel::query()->exists()
            ? collect()
            : collect();

        return view('frontend.member.guardian_mode.guardian_matches', [
            'link' => $link,
            'matches' => $matches,
            'feedback' => \App\Models\GuardianFeedback::where('guardian_link_id', $link->id)
                ->whereIn('target_user_id', $matches->pluck('match_id'))
                ->get()
                ->groupBy('target_user_id'),
            'permissionCatalog' => GuardianPermission::catalog(),
        ]);
    }
}
