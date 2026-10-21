<?php
declare(strict_types=1);
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\MatchOutcomeReview;
use App\Models\MatchSuccess;
use App\Models\HappyStory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
final class MatchOutcomeReviewController extends Controller {
    public function index(Request $request) {
        $data=$request->validate(['status'=>['nullable','in:pending,verified,rejected']]);
        $items=MatchOutcomeReview::with(['memberA','memberB','story','reviewer'])
            ->when($data['status']??null,fn($q,$s)=>$q->where('status',$s))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.client_scope.match_outcomes',compact('items'));
    }
    public function review(Request $request,MatchOutcomeReview $outcome) {
        $data=$request->validate([
            'status'=>['required','in:verified,rejected'],
            'verification_method'=>['required_if:status,verified','nullable','in:staff_contact,documents,other'],
            'happy_story_id'=>['nullable','integer','exists:happy_stories,id'],
            'internal_notes'=>['required_if:status,verified','nullable','string','min:20','max:2000']
        ]);
        DB::transaction(function()use($outcome,$data,$request){
            $row=MatchOutcomeReview::whereKey($outcome->id)->lockForUpdate()->firstOrFail();
            // Never treat a unilateral report as independent verification.
            $a=MatchSuccess::where('user_id',$row->member_a_id)->where('matched_user_id',$row->member_b_id)->where('status','got_match')->exists();
            $b=MatchSuccess::where('user_id',$row->member_b_id)->where('matched_user_id',$row->member_a_id)->where('status','got_match')->exists();
            if($data['status']==='verified' && (!$a||!$b)){
                throw ValidationException::withMessages(['status'=>'Both matched members must independently confirm before staff verification.']);
            }
            if(!empty($data['happy_story_id']) && !HappyStory::whereKey($data['happy_story_id'])->whereIn('user_id',[$row->member_a_id,$row->member_b_id])->exists()){
                throw ValidationException::withMessages(['happy_story_id'=>'Story must belong to a participant in the match.']);
            }
            DB::table('match_outcome_review_events')->insert([
                'match_outcome_review_id'=>$row->id,'actor_id'=>$request->user()->id,
                'previous_status'=>$row->status,'new_status'=>$data['status'],
                'verification_method'=>$data['verification_method']??null,'created_at'=>now()
            ]);
            $row->update(['status'=>$data['status'],'verification_method'=>$data['verification_method']??null,
                'happy_story_id'=>$data['happy_story_id']??null,'internal_notes'=>$data['internal_notes']??null,
                'reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
        });
        return back()->with('success','Outcome review recorded with audit history.');
    }
}
