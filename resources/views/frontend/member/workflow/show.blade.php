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
          <label for="hm-proposal-source">Message source</label><select id="hm-proposal-source" name="source"><option value="static">Static / My own message</option><option value="ai" disabled>AI (available when configured)</option></select>
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
  <dialog id="hm-locked-proposal"><h2>Send Interest First</h2><p>Before sending a proposal, you need to express your interest in this profile.</p><p>Send an interest request first. Once your interest is accepted, you can send a personalized marriage proposal.</p><div class="hm-connection-buttons"><button type="button" data-go-interest>Send Interest</button><button type="button" class="secondary" data-close-dialog>Cancel</button></div></dialog>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const root=document.querySelector('.hm-connection');if(!root)return;
 root.querySelectorAll('[data-template-select]').forEach(select=>select.addEventListener('change',()=>{const area=root.querySelector('[data-template-preview="'+select.dataset.templateSelect+'"]');if(area)area.value=select.selectedOptions[0]?.dataset.copy||'';}));
 const dialog=root.querySelector('#hm-locked-proposal');
 root.querySelector('[data-locked-proposal]')?.addEventListener('click',()=>dialog.showModal());
 root.querySelector('[data-close-dialog]')?.addEventListener('click',()=>dialog.close());
 root.querySelector('[data-go-interest]')?.addEventListener('click',()=>{dialog.close();root.querySelector('#hm-interest-template')?.focus();});
});
</script>
@endsection
