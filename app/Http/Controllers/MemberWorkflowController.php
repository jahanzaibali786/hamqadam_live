<?php

namespace App\Http\Controllers;

use App\Models\ExpressInterest;
use App\Models\MarriageProposal;
use App\Models\MemberCommunicationPreference;
use App\Models\User;
use App\Models\WorkflowMessageTemplate;
use App\Services\MemberConnectionWorkflow;
use Illuminate\Http\Request;

class MemberWorkflowController extends Controller
{
    public function __construct(private MemberConnectionWorkflow $workflow) {}

    public function show(Request $request, int $member)
    {
        $person=User::where('user_type','member')->whereKey($member)->firstOrFail();
        abort_if($person->id===$request->user()->id,404);
        $interest=ExpressInterest::where(fn ($q)=>$q->where('interested_by',$request->user()->id)->where('user_id',$member))
            ->orWhere(fn ($q)=>$q->where('interested_by',$member)->where('user_id',$request->user()->id))->latest('id')->first();
        $proposal=MarriageProposal::where(fn($q)=>$q->where('sender_id',$request->user()->id)->where('recipient_id',$member))
            ->orWhere(fn($q)=>$q->where('sender_id',$member)->where('recipient_id',$request->user()->id))->latest('id')->first();
        $templates=WorkflowMessageTemplate::active('interest')->get();
        $proposalTemplates=WorkflowMessageTemplate::active('proposal')->get();
        $unlocked=$this->workflow->canCommunicate($request->user()->id,$member);
        return view('frontend.member.workflow.show',compact('person','interest','proposal','templates','proposalTemplates','unlocked'));
    }
    public function interest(Request $request, int $member)
    {
        $data=$request->validate(['template_id'=>'required|integer|exists:workflow_message_templates,id','confirmed'=>'accepted']);
        $template=WorkflowMessageTemplate::active('interest')->findOrFail($data['template_id']);
        $recipient=User::findOrFail($member);
        $this->workflow->createInterest($request->user(),$recipient,$template);
        return back()->with('success','Interest sent. The recipient can now respond.');
    }
    public function respondInterest(Request $request, ExpressInterest $interest)
    {
        $data=$request->validate(['decision'=>'required|in:accept,decline']);
        $this->workflow->respondInterest($request->user(),$interest,$data['decision']==='accept');
        return back()->with('success','Interest response recorded.');
    }
    public function proposal(Request $request, int $member)
    {
        $data=$request->validate(['template_id'=>'nullable|integer','language'=>'required|in:en,ur','source'=>'required|in:static,ai','message'=>'required|string|min:20|max:5000','approved'=>'accepted','send'=>'required|in:yes,draft']);
        $this->workflow->saveProposal($request->user(),User::findOrFail($member),$data,$data['send']==='yes');
        return back()->with('success',$data['send']==='yes'?'Marriage proposal sent.':'Draft saved.');
    }
    public function respondProposal(Request $request, MarriageProposal $proposal)
    {
        $data=$request->validate(['decision'=>'required|in:accept,decline']);
        $this->workflow->answerProposal($request->user(),$proposal,$data['decision']==='accept');
        return back()->with('success','Proposal response recorded.');
    }
    public function preferences(Request $request)
    {
        $data=$request->validate(['allow_audio_calls'=>'required|boolean','allow_video_calls'=>'required|boolean']);
        MemberCommunicationPreference::updateOrCreate(['user_id'=>$request->user()->id],$data);
        return back()->with('success','Communication preferences saved.');
    }
}
