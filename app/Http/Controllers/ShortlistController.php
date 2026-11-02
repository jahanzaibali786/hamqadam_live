<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExpressInterest;
use App\Models\PackageUsage;
use App\Models\Shortlist;
use App\Models\User;
use App\Notifications\DbStoreNotification;
use Auth;
use Illuminate\Http\Request;
use Notification;

class ShortlistController extends Controller
{
    protected function getEffectiveUserId(): int
    {
        $userId = (int) Auth::id();
        $guardianLink = \App\Models\FamilyGuardianLink::where('guardian_user_id', $userId)
            ->where('status', 'approved')
            ->whereNull('revoked_at')
            ->whereNull('paused_at')
            ->first();
        if ($guardianLink && (Auth::user()->member == null || empty(Auth::user()->member->birthday))) {
            return (int) $guardianLink->profile_user_id;
        }
        return $userId;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $effectiveUserId = $this->getEffectiveUserId();
        $shortlists = Shortlist::where('shortlisted_by', $effectiveUserId)
            ->WhereNotIn("user_id", function ($query) use ($effectiveUserId) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', $effectiveUserId)->orWhere('user_id', $effectiveUserId);
            })
            ->WhereNotIn("user_id", function ($query) use ($effectiveUserId) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', $effectiveUserId)->orWhere('user_id', $effectiveUserId);
            })
            ->latest()->paginate(10);
        return view('frontend.member.my_shortlists', compact('shortlists'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $data = $request->validate(['id' => 'required|integer|min:1']);
        $result = app(\App\Services\OpenShortlistService::class)->add(Auth::user(), (int)$data['id']);
        return response((string)($result === null ? 0 : 1), 200);
    }
    public function remove(Request $request)
    {
        $effectiveUserId = $this->getEffectiveUserId();
        $shortlist = Shortlist::where('user_id', $request->id)->where('shortlisted_by', $effectiveUserId)->first();
        if ($shortlist && Shortlist::destroy($shortlist->id)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
    }
}
