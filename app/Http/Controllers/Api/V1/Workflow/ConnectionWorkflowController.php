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
    public const WORKFLOW_ID_OFFSET = 1000000000;

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
    /**
     * GET /connection-workflow/proposals?direction=sent|received|all
     *
     * Lists the reviewed proposals this member sent or received, in the same
     * shape as the legacy proposal list so the mobile app can show both. Ids are
     * offset by WORKFLOW_ID_OFFSET so they can never collide with legacy ids; the
     * real id is also returned as `workflow_id`.
     */
    public function proposals(Request $request): JsonResponse
    {
        $data=$request->validate(['direction'=>'nullable|in:sent,received,all','per_page'=>'nullable|integer|min:1|max:50']);
        $me=$request->user()->id; $direction=$data['direction']??'all';
        $query=MarriageProposal::with(['sender','recipient'])->where('status','!=','draft')->latest('id');
        match($direction){
            'sent'=>$query->where('sender_id',$me),
            'received'=>$query->where('recipient_id',$me),
            default=>$query->where(fn($q)=>$q->where('sender_id',$me)->orWhere('recipient_id',$me)),
        };
        $page=$query->paginate((int)($data['per_page']??20));
        $labels=['pending'=>[0,'pending'],'accepted'=>[1,'accepted'],'declined'=>[2,'rejected'],'withdrawn'=>[3,'withdrawn'],'cancelled'=>[4,'cancelled'],'expired'=>[5,'expired']];
        $items=collect($page->items())->map(function(MarriageProposal $p) use ($labels){
            [$value,$label]=$labels[$p->status]??[0,(string)$p->status];
            return [
                'id'=>self::WORKFLOW_ID_OFFSET+$p->id,'workflow_id'=>$p->id,
                'status'=>$label,'status_value'=>$value,
                'initial_note'=>$p->message,'compatibility_percentage'=>null,
                'family_stage'=>$p->family_stage,
                'sender'=>$p->sender?new \App\Http\Resources\Api\V1\Proposal\ProposalUserResource($p->sender):null,
                'recipient'=>$p->recipient?new \App\Http\Resources\Api\V1\Proposal\ProposalUserResource($p->recipient):null,
                'responded_at'=>$p->responded_at?->toISOString(),
                'created_at'=>$p->created_at?->toISOString(),'updated_at'=>$p->updated_at?->toISOString(),
            ];
        })->values();
        return response()->json(['success'=>true,'data'=>$items,'meta'=>[
            'current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'per_page'=>$page->perPage(),'total'=>$page->total(),
        ]]);
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
