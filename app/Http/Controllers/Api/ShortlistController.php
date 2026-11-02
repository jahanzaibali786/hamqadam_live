<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Shortlist;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ShortlistResource;

class ShortlistController extends Controller
{
    public function index()
    {
        $shortlists = Shortlist::where('shortlisted_by', auth()->user()->id)
            ->WhereNotIn("user_id", function ($query) {
                $query->select('user_id')
                    ->from('ignored_users')
                    ->where('ignored_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id);
            })
            ->WhereNotIn("user_id", function ($query) {
                $query->select('ignored_by')
                    ->from('ignored_users')
                    ->where('ignored_by', auth()->user()->id)->orWhere('user_id', auth()->user()->id);
            })
            ->latest()->paginate(10);

        return ShortlistResource::collection($shortlists)->additional([
            'result' => true
        ]);
    }

    public function store(Request $request)
    {
        $data=$request->validate(['user_id'=>'required|integer|min:1']);
        $result=app(\App\Services\OpenShortlistService::class)->add($request->user(),(int)$data['user_id']);
        return $result === null ? $this->failure_message('This profile is unavailable to shortlist.')
            : $this->success_message($result ? 'You have shortlisted this member.' : 'Already shortlisted.');
    }
    public function remove(Request $request)
    {
        $shortlist = Shortlist::where('user_id', $request->user_id)->where('shortlisted_by', auth()->user()->id)->first();
        if ($shortlist) {
            Shortlist::destroy($shortlist->id);
            return $this->success_message('You Have Removed This Member From Your Shortlist.');
        }
        return $this->success_message('Invalid Information');
    }
}
