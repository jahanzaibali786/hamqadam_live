<?php

namespace App\Http\Controllers;

use App\Models\ProfileViewer;
use Illuminate\Http\Request;

class ProfileViewerController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'received');

        $receivedProfileViewers = ProfileViewer::with([
            'profileViewer.member',
            'profileViewer.addresses',
            'profileViewer.education',
            'profileViewer.career',
            'profileViewer.physical_attributes',
            'profileViewer.spiritual_backgrounds',
        ])
            ->where('user_id', auth()->id())
            ->whereIn('viewed_by', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('approved', '1')
                    ->where('blocked', 0)
                    ->where('deactivated', 0)
                    ->where('permanently_delete', 0);
            })
            ->latest()
            ->paginate(10, ['*'], 'received_page');

        $viewedProfiles = ProfileViewer::with([
            'user.member',
            'user.addresses',
            'user.education',
            'user.career',
            'user.physical_attributes',
            'user.spiritual_backgrounds',
        ])
            ->where('viewed_by', auth()->id())
            ->whereIn('user_id', function ($query) {
                $query->select('id')
                    ->from('users')
                    ->where('approved', '1')
                    ->where('blocked', 0)
                    ->where('deactivated', 0)
                    ->where('permanently_delete', 0);
            })
            ->latest()
            ->paginate(10, ['*'], 'viewed_page');

        return view('frontend.member.my_profile_viewers', compact('receivedProfileViewers', 'viewedProfiles', 'activeTab'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
