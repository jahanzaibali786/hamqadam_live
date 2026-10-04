<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\AnalyticsEvent;
use App\Models\MatchSuccess;
use App\Models\RewardTransaction;
use App\Models\SatisfactionSurvey;
use App\Models\SponsoredListing;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompletionController extends ApiController
{
    public function track(Request $request): JsonResponse
    {
        $data=$request->validate(['event_name'=>'required|string|max:100','feature_key'=>'nullable|string|max:100','session_key'=>'nullable|string|max:120','country'=>'nullable|string|max:100','region'=>'nullable|string|max:100','city'=>'nullable|string|max:100','platform'=>'nullable|string|max:40','source'=>'nullable|string|max:80','metadata'=>'nullable|array']);
        $data['user_id']=$request->user()?->id; $data['occurred_at']=now(); AnalyticsEvent::create($data);
        return $this->success(null,'Event recorded.',201);
    }
    public function nps(Request $request): JsonResponse
    {
        $data=$request->validate(['score'=>'required|integer|between:0,10','comment'=>'nullable|string|max:2000','context'=>'nullable|string|max:80','platform'=>'nullable|string|max:40']);
        $survey=SatisfactionSurvey::create($data+['user_id'=>$request->user()->id,'context'=>$data['context']??'nps']);
        return $this->success($survey,'Thank you for your feedback.',201);
    }
    public function gotMatch(Request $request): JsonResponse
    {
        $data=$request->validate(['matched_user_id'=>'required|integer|exists:users,id|different:user_id','profile_match_id'=>'nullable|integer|exists:profile_matches,id','note'=>'nullable|string|max:2000']);
        $match=MatchSuccess::firstOrCreate(['user_id'=>$request->user()->id,'matched_user_id'=>$data['matched_user_id'],'status'=>'got_match'],['profile_match_id'=>$data['profile_match_id']??null,'note'=>$data['note']??null,'confirmed_at'=>now()]);
        AnalyticsEvent::create(['user_id'=>$request->user()->id,'event_name'=>'got_match','feature_key'=>'match_success','metadata'=>['matched_user_id'=>$data['matched_user_id']],'occurred_at'=>now()]);
        return $this->success($match,'Successful match recorded.');
    }
    public function sponsored(Request $request): JsonResponse
    {
        $member=$request->user()->member; $flags=(array)($member?->package?->feature_flags??[]); if(in_array('ad_free',$flags,true)) return $this->success([]);
        $items=SponsoredListing::live()->orderByDesc('priority')->limit(12)->get(); return $this->success($items);
    }
    public function rewards(Request $request): JsonResponse
    {
        $items=RewardTransaction::with('rule')->where('user_id',$request->user()->id)->latest()->paginate(20);
        return ApiResponse::paginated($items);
    }
    public function profileLink(Request $request): JsonResponse
    {
        return $this->success(['url'=>route('profile.share',['user'=>$request->user()->id])]);
    }
}
