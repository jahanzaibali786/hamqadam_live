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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shortlists = Shortlist::where('shortlisted_by', Auth::user()->id)
            ->WhereNotIn("user_id", function ($query) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            })
            ->WhereNotIn("user_id", function ($query) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
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
        $shortlisted_by_user = Auth::user();
        $shortlisted_by_member = $shortlisted_by_user->member;
        $targetUser = User::find($request->id);
        $coinCost = feature_coin_cost('shortlist', 5);

        if ($targetUser == null || $targetUser->id == $shortlisted_by_user->id) {
            return 0;
        }

        $hasAcceptedInterest = ExpressInterest::where(function ($query) use ($shortlisted_by_user, $targetUser) {
                $query->where('user_id', $shortlisted_by_user->id)
                    ->where('interested_by', $targetUser->id);
            })
            ->where('status', 1)
            ->exists()
            || ExpressInterest::where(function ($query) use ($shortlisted_by_user, $targetUser) {
                $query->where('user_id', $targetUser->id)
                    ->where('interested_by', $shortlisted_by_user->id);
            })
            ->where('status', 1)
            ->exists();

        if (!$hasAcceptedInterest) {
            return 0;
        }

        if ($shortlisted_by_member == null || $shortlisted_by_member->remaining_interest < $coinCost) {
            return 0;
        }

        $shortlist = Shortlist::firstOrNew([
            'user_id'        => $request->id,
            'shortlisted_by' => $shortlisted_by_user->id,
        ]);

        if ($shortlist->exists) {
            return 0;
        }

        if ($shortlist->save()) {
            $shortlisted_by_member->remaining_interest -= $coinCost;
            $shortlisted_by_member->save();

            PackageUsage::record(
                $shortlisted_by_user->id,
                'shortlist',
                'Shortlist',
                $coinCost,
                Shortlist::class,
                $shortlist->id,
                'Used ' . $coinCost . ' coins to shortlist member.'
            );

            try {
                $notify_type = 'shortlist';
                $id = unique_notify_id();
                $notify_by = $shortlisted_by_user->id;
                $info_id = $shortlist->id;
                $message = $shortlisted_by_user->first_name . ' ' . $shortlisted_by_user->last_name . ' ' . translate(' has shortlisted you.');
                $route = route('my_shortlists');

                Notification::send($targetUser, new DbStoreNotification($notify_type, $id, $notify_by, $info_id, $message, $route));
            } catch (\Exception $e) {
                // ignore notification failure
            }

            return 1;
        } else {
            return 0;
        }
    }
    public function remove(Request $request)
    {
        $shortlist = Shortlist::where('user_id', $request->id)->where('shortlisted_by', Auth::user()->id)->first()->id;
        if (Shortlist::destroy($shortlist)) {
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
