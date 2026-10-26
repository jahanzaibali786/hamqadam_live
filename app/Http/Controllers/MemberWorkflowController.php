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
        $meetings=$proposal?->status==='accepted' ? \App\Models\ProposalMeeting::where('express_interest_id',$proposal->interest_id)->orderByDesc('scheduled_at')->get() : collect();
        return view('frontend.member.workflow.show',compact('person','interest','proposal','templates','proposalTemplates','unlocked','meetings'));
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
    public function aiDraft(Request $request, int $member)
    {
        $data=$request->validate([
            'language'=>'required|in:en,ur',
            'intention'=>'required|string|min:10|max:700',
        ]);
        $draft=app(\App\Services\AiProposalDraftService::class)->generate(
            $request->user(),User::findOrFail($member),$data['language'],$data['intention']
        );
        return response()->json(['draft'=>$draft,'source'=>'ai','requires_approval'=>true]);
    }

    public function meeting(Request $request, MarriageProposal $proposal)
    {
        abort_unless($proposal->status==='accepted' && in_array($request->user()->id,[$proposal->sender_id,$proposal->recipient_id],true),403);
        $data=$request->validate([
            'meeting_type'=>'required|in:family,virtual,in_person',
            'scheduled_at'=>'required|date|after:now',
            'duration_minutes'=>'required|integer|min:10|max:180',
            'location'=>'nullable|string|max:255',
            'meeting_url'=>'nullable|url|max:500',
            'notes'=>'nullable|string|max:2000',
        ]);
        $meeting=\App\Models\ProposalMeeting::create([
            'express_interest_id'=>$proposal->interest_id,
            'organizer_user_id'=>$request->user()->id,
            'meeting_type'=>$data['meeting_type'],
            'scheduled_at'=>$data['scheduled_at'],
            'duration_minutes'=>$data['duration_minutes'],
            'location'=>$data['location']??null,
            'meeting_url'=>$data['meeting_url']??null,
            'notes'=>$data['notes']??null,
        ]);
        foreach ([$proposal->sender,$proposal->recipient] as $person) {
            \App\Services\NotificationHelper::event($person,'workflow_family_meeting_scheduled',[
                'event_id'=>'meeting-'.$meeting->id,'info_id'=>$meeting->id,'notify_by'=>$request->user()->id
            ]);
        }
        return back()->with('success','Meeting scheduled.');
    }

    public function meetingNotes(Request $request, \App\Models\ProposalMeeting $meeting)
    {
        $proposal=MarriageProposal::where('interest_id',$meeting->express_interest_id)->where('status','accepted')->latest('id')->firstOrFail();
        abort_unless(in_array($request->user()->id,[$proposal->sender_id,$proposal->recipient_id],true),403);
        $data=$request->validate(['notes'=>'required|string|max:2000']);
        $meeting->update(['notes'=>$data['notes']]);
        return back()->with('success','Meeting notes updated.');
    }

    public function relationshipStatus(Request $request, MarriageProposal $proposal)
    {
        abort_unless($proposal->status==='accepted' && in_array($request->user()->id,[$proposal->sender_id,$proposal->recipient_id],true),403);
        $data=$request->validate(['status'=>'required|in:engaged,nikah,married,cancelled','status_date'=>'nullable|date','notes'=>'nullable|string|max:2000']);
        \App\Models\RelationshipStatusUpdate::create([
            'user_id'=>$request->user()->id,
            'partner_user_id'=>$proposal->sender_id===$request->user()->id?$proposal->recipient_id:$proposal->sender_id,
            'express_interest_id'=>$proposal->interest_id,
            'status'=>$data['status'],
            'status_date'=>$data['status_date']??now()->toDateString(),
            'notes'=>$data['notes']??null,
            'is_public'=>false,
            'moderation_status'=>'pending',
        ]);
        $proposal->update(['family_stage'=>$data['status']]);
        return back()->with('success','Relationship status submitted for review.');
    }

    public function preferences(Request $request)
    {
        $data=$request->validate(['allow_audio_calls'=>'required|boolean','allow_video_calls'=>'required|boolean']);
        MemberCommunicationPreference::updateOrCreate(['user_id'=>$request->user()->id],$data);
        return back()->with('success','Communication preferences saved.');
    }
}
