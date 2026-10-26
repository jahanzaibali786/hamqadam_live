@extends('frontend.layouts.member_panel')
@section('panel_content')
<link rel="stylesheet" href="{{ static_asset('assets/css/hamqadam-connection-workflow.css') }}?v=1">
@php
$stage=$proposal?->status==='accepted'?'family':($proposal?->status==='pending'?'proposal':($unlocked?'connected':($interest?'interest':'discover')));
$sender=(int)$interest?->interested_by===(int)auth()->id();
@endphp
<div class="hm-connection">
  <header><a href="{{ route('my_matched_profiles') }}"><i class="las la-arrow-left"></i> Matched Profiles</a><h1>Interest, Proposal & Connection</h1><p>Get to know {{ $person->first_name }} at a comfortable pace.</p></header>
  @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
  @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <nav class="hm-connection-steps" aria-label="Connection stages"><span class="{{ $stage==='discover'?'current':'' }}">1. Send Interest</span><span class="{{ $stage==='interest'?'current':'' }}">2. Interest Response</span><span class="{{ $stage==='connected'?'current':'' }}">3. Chat & Calls</span><span class="{{ $stage==='proposal'?'current':'' }}">4. Proposal</span><span class="{{ $stage==='family'?'current':'' }}">5. Family & Meeting</span></nav>
  <div class="hm-connection-grid">
    <section class="hm-connection-card"><h2><i class="las la-heart"></i> Send Interest</h2><p>Choose a respectful message, review it, then confirm.</p>
      @if(!$interest || !in_array((int)($interest->status instanceof \BackedEnum?$interest->status->value:$interest->status),[0,1],true))
        <form action="{{ route('member.workflow.interest',$person->id) }}" method="POST">@csrf
          <label for="hm-interest-template">Message template</label><select id="hm-interest-template" name="template_id" data-template-select="interest" required><option value="">Select a message</option>@foreach($templates as $template)<option value="{{$template->id}}" data-copy="{{$template->complete_message}}">{{$template->title}}</option>@endforeach</select>
          <label for="hm-interest-preview">Preview</label><textarea id="hm-interest-preview" data-template-preview="interest" rows="6" readonly aria-label="Interest preview"></textarea>
          <label class="hm-connection-check"><input type="checkbox" name="confirmed" value="1" required> I approve this interest message.</label>
          <button type="submit">Confirm & Send Interest</button>
        </form>
      @else
        <div class="hm-connection-status">Interest: {{ ucfirst(strtolower($interest->status instanceof \BackedEnum?$interest->status->name:(string)$interest->status)) }}</div>
        <blockquote>{{ $interest->initial_note }}</blockquote>
        @if((int)$interest->user_id===auth()->id() && (int)($interest->status instanceof \BackedEnum?$interest->status->value:$interest->status)===0)
          @foreach(['accept'=>'Accept','decline'=>'Decline'] as $decision=>$label)<form method="POST" action="{{route('member.workflow.interest.respond',$interest->id)}}">@csrf<input type="hidden" name="decision" value="{{$decision}}"><button type="submit" class="{{$decision==='decline'?'secondary':''}}">{{$label}} Interest</button></form>@endforeach
        @endif
      @endif
    </section>
    <section class="hm-connection-card"><h2><i class="las la-envelope"></i> Send Proposal @unless($unlocked)<i class="las la-lock" title="Requires accepted interest"></i>@endunless</h2>
      <p>Marriage proposals require accepted interest. You can select a template, edit your message, and approve it before sending.</p>
      @if(!$unlocked)<button type="button" data-locked-proposal>Send Proposal <i class="las la-lock"></i></button>
      @elseif($proposal && in_array($proposal->status,['pending','accepted'],true))
        <div class="hm-connection-status">{{ucfirst($proposal->status)}} Marriage Proposal</div><blockquote>{{ $proposal->message }}</blockquote>
        @if($proposal->status==='pending' && (int)$proposal->recipient_id===auth()->id())
        @foreach(['accept'=>'Accept','decline'=>'Decline'] as $decision=>$label)<form method="POST" action="{{route('member.workflow.proposal.respond',$proposal->id)}}">@csrf<input type="hidden" name="decision" value="{{$decision}}"><button type="submit" class="{{$decision==='decline'?'secondary':''}}">{{$label}} Proposal</button></form>@endforeach
        @endif
      @else
        <form action="{{route('member.workflow.proposal',$person->id)}}" method="POST">@csrf
          <label for="hm-proposal-template">Proposal template</label><select id="hm-proposal-template" name="template_id" data-template-select="proposal"><option value="">Write your own</option>@foreach($proposalTemplates as $template)<option value="{{$template->id}}" data-copy="{{$template->complete_message}}">{{$template->title}}</option>@endforeach</select>
          <label for="hm-proposal-language">Language</label><select name="language" id="hm-proposal-language"><option value="en">English</option><option value="ur">Urdu</option></select>
          <label for="hm-proposal-source">Message source</label><select id="hm-proposal-source" name="source"><option value="static">Static / My own message</option><option value="ai">AI-generated draft</option></select>
          <div class="hm-connection-ai"><label for="hm-proposal-intention">Your intentions for AI drafting</label><textarea id="hm-proposal-intention" placeholder="Describe your genuine intentions and whether you wish to mention family introduction." rows="3" minlength="10" maxlength="700"></textarea><button type="button" id="hm-ai-generate">Generate / Regenerate AI Draft</button><p id="hm-ai-status" role="status" aria-live="polite">AI drafts require an enabled provider. Static templates always remain available.</p></div>
          <label for="hm-proposal-message">Editable message & preview</label><textarea id="hm-proposal-message" data-template-preview="proposal" name="message" rows="9" required minlength="20" maxlength="5000">{{ $proposal?->status==='draft'?$proposal->message:'' }}</textarea>
          <label class="hm-connection-check"><input type="checkbox" name="approved" value="1" required> I have reviewed and approve this exact text.</label>
          <div class="hm-connection-buttons"><button type="submit" name="send" value="draft" >Save Draft</button><button type="submit" name="send" value="yes">Send Proposal</button></div>
        </form>
      @endif
    </section>
  </div>
  <section class="hm-connection-card"><h2>Communication & Next Steps</h2><p>{{ $unlocked?'Interest accepted. Messaging and calls are available subject to both members’ preferences.':'Chat, audio calls and video calls unlock only after interest acceptance.' }}</p>
    @if($unlocked)<a class="hm-connection-link" href="{{route('all.messages')}}">Open Chat & Calls</a>@endif
    @if($proposal?->status==='accepted')<p class="hm-connection-status">Proposal accepted. Family introduction and meeting planning can begin by mutual agreement.</p><a class="hm-connection-link" href="{{route('member.reference.meetings')}}">Plan a Family Meeting</a>@endif
    <form action="{{route('member.workflow.preferences')}}" method="POST">@csrf<h3>My Call Preferences</h3>
      @php($prefs=\App\Models\MemberCommunicationPreference::where('user_id',auth()->id())->first())
      <label>Audio calls <select name="allow_audio_calls"><option value="1" @selected($prefs?->allow_audio_calls!==false)>Allowed</option><option value="0" @selected($prefs?->allow_audio_calls===false)>Disabled</option></select></label>
      <label>Video calls <select name="allow_video_calls"><option value="1" @selected($prefs?->allow_video_calls!==false)>Allowed</option><option value="0" @selected($prefs?->allow_video_calls===false)>Disabled</option></select></label><button type="submit">Save Preferences</button>
    </form>
  </section>
  @if($proposal?->status==='accepted')
    <section class="hm-connection-card">
      <h2><i class="las la-users"></i> Family Discussion & Meeting Planning</h2>
      <p>With your proposal accepted, you may introduce families and schedule a mutually comfortable meeting.</p>
      <form method="POST" action="{{route('member.workflow.meeting',$proposal->id)}}">@csrf
        <label for="hm-meeting-type">Meeting type</label><select name="meeting_type" id="hm-meeting-type"><option value="family">Family introduction</option><option value="virtual">Virtual meeting</option><option value="in_person">In person</option></select>
        <label for="hm-meeting-time">Date and time</label><input class="form-control" type="datetime-local" name="scheduled_at" id="hm-meeting-time" required>
        <label>Duration (minutes)</label><input class="form-control" name="duration_minutes" type="number" min="10" max="180" value="30" required>
        <label>Location</label><input class="form-control" name="location" maxlength="255" placeholder="Only share with mutual consent">
        <label>Video meeting URL (optional)</label><input class="form-control" name="meeting_url" type="url" maxlength="500">
        <label>Notes / agenda</label><textarea name="notes" maxlength="2000" rows="3"></textarea>
        <button type="submit">Schedule Family Meeting</button>
      </form>
      <h3>Scheduled Meetings</h3>
      @forelse($meetings as $meeting)
        <div class="hm-connection-status"><strong>{{ucwords(str_replace('_',' ',$meeting->meeting_type))}}</strong> — {{ $meeting->scheduled_at?->format('d M Y, h:i A') }} ({{ $meeting->status ?: 'scheduled' }})</div>
        <form method="POST" action="{{route('member.workflow.meeting.notes',$meeting->id)}}">@csrf<label for="hm-meeting-note-{{$meeting->id}}">Meeting notes / follow-up</label><textarea id="hm-meeting-note-{{$meeting->id}}" name="notes" maxlength="2000" rows="3" required>{{ $meeting->notes }}</textarea><button type="submit">Save Follow-up Notes</button></form>
      @empty <p>No family meetings scheduled yet.</p>
      @endforelse
      <h3>Relationship Milestone</h3><p>Submit the next milestone for moderation. Neither member's status is made public automatically.</p>
      <form method="POST" action="{{route('member.workflow.relationship',$proposal->id)}}">@csrf
        <label>Status</label><select name="status"><option value="engaged">Engaged</option><option value="nikah">Nikah</option><option value="married">Married</option><option value="cancelled">Cancelled</option></select>
        <label>Effective date</label><input class="form-control" name="status_date" type="date" value="{{now()->toDateString()}}">
        <label>Notes</label><textarea name="notes" maxlength="2000" rows="2"></textarea><button type="submit">Submit Status for Review</button>
      </form>
    </section>
  @endif
  <dialog id="hm-locked-proposal"><h2>Send Interest First</h2><p>Before sending a proposal, you need to express your interest in this profile.</p><p>Send an interest request first. Once your interest is accepted, you can send a personalized marriage proposal.</p><div class="hm-connection-buttons"><button type="button" data-go-interest>Send Interest</button><button type="button" class="secondary" data-close-dialog>Cancel</button></div></dialog>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.querySelector('.hm-connection');if(!root)return;
 root.querySelectorAll('[data-template-select]').forEach(select=>select.addEventListener('change',()=>{const area=root.querySelector('[data-template-preview="'+select.dataset.templateSelect+'"]');if(area)area.value=select.selectedOptions[0]?.dataset.copy||'';}));
 const aiButton=root.querySelector('#hm-ai-generate');
 aiButton?.addEventListener('click',async()=>{
   const intention=root.querySelector('#hm-proposal-intention')?.value.trim()||'';
   const status=root.querySelector('#hm-ai-status');
   const language=root.querySelector('#hm-proposal-language')?.value||'en';
   if(intention.length<10){status.textContent='Please describe your intention in at least 10 characters.';return;}
   aiButton.disabled=true;status.textContent='Generating a draft for your review…';
   try {
     const response=await fetch(@json(route('member.workflow.proposal.generate',$person->id)),{
       method:'POST',credentials:'same-origin',
       headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},
       body:JSON.stringify({language,intention})
     });
     const result=await response.json();
     if(!response.ok||!result.draft)throw new Error(result.message||result.errors?.ai?.[0]||'Provider unavailable');
     root.querySelector('#hm-proposal-message').value=result.draft;
     root.querySelector('#hm-proposal-source').value='ai';
     root.querySelector('input[name=approved][type=checkbox]').checked=false;
     status.textContent='Draft generated. Please edit or preview it and approve the final wording before sending.';
   } catch(e){status.textContent='AI unavailable. Choose a static template or write your own message.';}
   finally{aiButton.disabled=false;}
 });
 const dialog=root.querySelector('#hm-locked-proposal');
 root.querySelector('[data-locked-proposal]')?.addEventListener('click',()=>dialog.showModal());
 root.querySelector('[data-close-dialog]')?.addEventListener('click',()=>dialog.close());
 root.querySelector('[data-go-interest]')?.addEventListener('click',()=>{dialog.close();root.querySelector('#hm-interest-template')?.focus();});
});
</script>
@endsection
