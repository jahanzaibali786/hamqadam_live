<?php

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Http\Controllers\Controller;
use App\Models\ExpressInterest;
use App\Models\MarriageProposal;
use App\Models\MemberCommunicationPreference;
use App\Models\User;
use App\Models\WorkflowMessageTemplate;
use App\Services\MemberConnectionWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionWorkflowController extends Controller
{
    public function __construct(private MemberConnectionWorkflow $workflow) {}

    public function templates(Request $request): JsonResponse
    {
        $data=$request->validate(['kind'=>'required|in:interest,proposal','language'=>'nullable|string|max:12']);
        $items=WorkflowMessageTemplate::active($data['kind'],$data['language']??'en')->get()->map(fn($x)=>[
            'id'=>$x->id,'title'=>$x->title,'language'=>$x->language,
            'opening'=>$x->opening,'body'=>$x->body,'closing'=>$x->closing,
            'preview'=>$x->complete_message,
        ]);
        return response()->json(['data'=>$items]);
    }
    public function state(Request $request, int $member): JsonResponse
    {
        $person=User::where('user_type','member')->findOrFail($member);$viewer=$request->user();
        $interest=ExpressInterest::where('interaction_type','interest')->where(function($q) use ($viewer,$member){
            $q->where(fn($x)=>$x->where('interested_by',$viewer->id)->where('user_id',$member))
              ->orWhere(fn($x)=>$x->where('interested_by',$member)->where('user_id',$viewer->id));
        })->latest('id')->first();
        $proposal=MarriageProposal::where(function($q) use($viewer,$member){
            $q->where(fn($x)=>$x->where('sender_id',$viewer->id)->where('recipient_id',$member))
              ->orWhere(fn($x)=>$x->where('sender_id',$member)->where('recipient_id',$viewer->id));
        })->where(fn($q)=>$q->where('status','!=','draft')->orWhere('sender_id',$viewer->id))->latest('id')->first();
        return response()->json(['data'=>[
            'member_id'=>$person->id,
            'interest'=>$interest?->only(['id','status','initial_note','responded_at']),
            'proposal'=>$proposal?->only(['id','status','message','family_stage','responded_at']),
            'chat_enabled'=>$this->workflow->canCommunicate($viewer->id,$member),
            'audio_enabled'=>$this->workflow->canCall($viewer->id,$member,'audio'),
            'video_enabled'=>$this->workflow->canCall($viewer->id,$member,'video'),
        ]]);
    }
    public function interest(Request $request,int $member): JsonResponse
    {
        $data=$request->validate(['template_id'=>'required|integer','approved'=>'accepted']);
        $template=WorkflowMessageTemplate::active('interest')->findOrFail($data['template_id']);
        $interest=$this->workflow->createInterest($request->user(),User::findOrFail($member),$template);
        return response()->json(['data'=>['id'=>$interest->id,'status'=>'pending']],201);
    }
    public function respondInterest(Request $request,ExpressInterest $interest): JsonResponse
    {
        $data=$request->validate(['decision'=>'required|in:accept,decline']);
        $item=$this->workflow->respondInterest($request->user(),$interest,$data['decision']==='accept');
        return response()->json(['data'=>['id'=>$item->id,'status'=>$data['decision']==='accept'?'accepted':'declined']]);
    }
    public function proposal(Request $request,int $member): JsonResponse
    {
        $data=$request->validate(['template_id'=>'nullable|integer','language'=>'required|in:en,ur','source'=>'required|in:static,ai','message'=>'required|string|min:20|max:5000','approved'=>'accepted','send'=>'required|boolean']);
        $proposal=$this->workflow->saveProposal($request->user(),User::findOrFail($member),$data,(bool)$data['send']);
        return response()->json(['data'=>['id'=>$proposal->id,'status'=>$proposal->status]],$data['send']?201:200);
    }
    public function respondProposal(Request $request,MarriageProposal $proposal): JsonResponse
    {
        $data=$request->validate(['decision'=>'required|in:accept,decline']);
        $proposal=$this->workflow->answerProposal($request->user(),$proposal,$data['decision']==='accept');
        return response()->json(['data'=>['id'=>$proposal->id,'status'=>$proposal->status,'family_stage'=>$proposal->family_stage]]);
    }
    public function aiDraft(Request $request,int $member): JsonResponse
    {
        $data=$request->validate(['language'=>'required|in:en,ur','intention'=>'required|string|min:10|max:700']);
        $draft=app(\App\Services\AiProposalDraftService::class)->generate($request->user(),User::findOrFail($member),$data['language'],$data['intention']);
        return response()->json(['data'=>['draft'=>$draft,'requires_approval'=>true,'source'=>'ai']]);
    }
    public function preferences(Request $request): JsonResponse
    {
        if($request->isMethod('get')){
            return response()->json(['data'=>MemberCommunicationPreference::firstOrCreate(['user_id'=>$request->user()->id])]);
        }
        $data=$request->validate(['allow_audio_calls'=>'required|boolean','allow_video_calls'=>'required|boolean']);
        $item=MemberCommunicationPreference::updateOrCreate(['user_id'=>$request->user()->id],$data);
        return response()->json(['data'=>$item]);
    }
}
