@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference hm-parity-meetings"><header class="hm-reference-heading"><div><span class="hm-ref-kicker">Meaningful connections</span><h1>Schedule a Meeting</h1><p>Take the next step with a safe, respectful and meaningful meeting.</p></div></header>
<div class="hm-parity-meeting-progress"><span class="current"><b>1</b> Meeting Type</span><span><b>2</b> Select Date &amp; Time</span><span><b>3</b> Confirm Details</span><span><b>4</b> Scheduled</span></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="hm-ref-layout"><main class="hm-ref-white">
<form action="{{ route('member.reference.meetings.store') }}" method="POST">@csrf
<h2>1. Choose Meeting Type</h2><div class="hm-parity-meeting-types">
@foreach([['virtual','Virtual Meeting','One-to-one video meeting','la-video'],['family','Family Meeting','Include family participation','la-users'],['in_person','In-Person Meeting','Meet at a public location','la-calendar-check']] as $type)
<label><input type="radio" name="meeting_type" value="{{ $type[0] }}" {{ old('meeting_type','virtual')===$type[0]?'checked':'' }}><span><i class="las {{ $type[3] }}"></i><b>{{ $type[1] }}</b><small>{{ $type[2] }}</small></span></label>
@endforeach</div>
<h2>2. Select Date &amp; Time</h2><div class="hm-parity-meeting-fields">
<label>Date &amp; Time <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required></label>
<label>Duration <select class="form-control" name="duration_minutes" required><option value="30">30 minutes</option><option value="60">60 minutes</option><option value="90">90 minutes</option></select></label>
</div><div class="hm-parity-meeting-slots"><span>Suggested durations:</span><button type="button" data-duration="30">30 min</button><button type="button" data-duration="60">60 min</button><button type="button" data-duration="90">90 min</button></div>
<h2>3. Additional Options</h2><div class="hm-parity-meeting-fields"><label>Meeting URL (optional)<input class="form-control" type="url" name="meeting_url" value="{{ old('meeting_url') }}" placeholder="https://..."></label><label>Public location for an in-person meeting<input class="form-control" name="location" value="{{ old('location') }}" placeholder="Venue and city"></label></div>
<label class="hm-parity-meeting-guardian"><input type="checkbox" name="chaperone_mode" value="1" @checked(old('chaperone_mode'))> Include my approved guardian</label>
<label class="d-block mt-3">Notes (optional)<textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea></label>
<h2>4. Confirm &amp; Schedule</h2><div class="hm-parity-meeting-confirm"><label>Select accepted proposal<select class="form-control" name="proposal_id" required><option value="">Choose a member</option>@foreach($proposals as $proposal)@php($partner=((int)$proposal->user_id===(int)auth()->id()?$proposal->sender:$proposal->recipient))<option value="{{ $proposal->id }}" @selected(old('proposal_id')==$proposal->id)>{{ $partner?->first_name }} {{ $partner?->last_name }} · Proposal #{{ $proposal->id }}</option>@endforeach</select></label><button class="hm-ref-cta" type="submit" @disabled($proposals->isEmpty())>Schedule Meeting →</button></div>
@if($proposals->isEmpty())<p class="small text-muted mt-3">Only accepted proposals may be scheduled. Once a proposal is accepted, the member will appear in the selector.</p>@endif
</form></main>
<aside><section class="hm-ref-white"><h2>You Are Scheduling With</h2><p class="small text-muted">Select an accepted proposal to confirm the other participant. Their details are protected by your existing member permissions.</p></section>
<section class="hm-ref-white"><h2><i class="las la-shield-alt"></i> Meeting Safety Tips</h2><ul class="hm-parity-meeting-safety"><li>Use approved in-app or mutually agreed meeting links</li><li>Be respectful and maintain decorum</li><li>Do not share personal contact details unnecessarily</li><li>Report inappropriate behavior</li></ul></section>
<section class="hm-ref-white"><h2>Upcoming &amp; Recent</h2>@forelse($meetings as $meeting)<div class="hm-ref-entry"><b>{{ $meeting->scheduled_at?->format('d M Y · h:i A') }}</b><span>{{ ucfirst(str_replace('_',' ',$meeting->meeting_type)) }} · {{ $meeting->duration_minutes }} min</span></div>@empty<p class="small text-muted">No scheduled meeting records yet.</p>@endforelse</section></aside></div></div>
<script>(function(){document.querySelectorAll('.hm-parity-meeting-slots button').forEach(b=>b.addEventListener('click',()=>{let s=document.querySelector('.hm-parity-meetings select[name=duration_minutes]');if(s)s.value=b.dataset.duration;}));})();</script>
@endsection