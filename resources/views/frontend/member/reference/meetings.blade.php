@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference">
<header class="hm-reference-heading"><span class="hm-ref-kicker">Meaningful connections</span><h1>Meeting Scheduling</h1><p>Arrange respectful conversations with an accepted proposal.</p></header>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="hm-ref-layout"><section class="hm-ref-white">
<div class="hm-ref-section-title"><h2>Schedule a Meeting</h2><span>Accepted proposals only</span></div>
<form action="{{ route('member.reference.meetings.store') }}" method="POST">@csrf
<div class="hm-ref-fields"><label>Who would you like to meet?<select class="form-control" name="proposal_id" required><option value="">Choose an accepted proposal</option>@foreach($proposals as $proposal)@php($partner=((int)$proposal->user_id===(int)auth()->id()?$proposal->sender:$proposal->recipient))<option value="{{ $proposal->id }}" @selected(old('proposal_id')==$proposal->id)>{{ $partner?->first_name }} {{ $partner?->last_name }} · Proposal #{{ $proposal->id }}</option>@endforeach</select></label>
<label>Meeting Type<select class="form-control" name="meeting_type" required><option value="virtual">Virtual Meeting</option><option value="family">Family Meeting</option><option value="in_person">In Person</option></select></label>
<label>Preferred Date &amp; Time<input class="form-control" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}" required></label>
<label>Duration<select class="form-control" name="duration_minutes"><option value="30">30 minutes</option><option value="60">60 minutes</option><option value="90">90 minutes</option></select></label>
<label>Video Meeting Link (optional)<input class="form-control" name="meeting_url" type="url" placeholder="https://..." value="{{ old('meeting_url') }}"></label>
<label>Public Meeting Location (in-person only)<input class="form-control" name="location" value="{{ old('location') }}" placeholder="Public venue and city"></label>
</div><label class="d-block mt-3"><input type="checkbox" name="chaperone_mode" value="1"> Include an approved guardian</label><label class="d-block mt-3">Notes<textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea></label>
<button type="submit" class="hm-ref-cta mt-3" @disabled($proposals->isEmpty())><i class="las la-calendar-plus"></i> Schedule Meeting</button>
@if($proposals->isEmpty())<p class="small text-muted mt-3">Meetings can be scheduled once a proposal has been accepted.</p>@endif
</form></section>
<aside><section class="hm-ref-white"><div class="hm-ref-section-title"><h2>Upcoming &amp; Recent</h2></div>@forelse($meetings as $meeting)<div class="hm-ref-entry"><b>{{ $meeting->scheduled_at?->format('d M Y · h:i A') }}</b><span>{{ ucfirst(str_replace('_',' ',$meeting->meeting_type)) }} · {{ $meeting->duration_minutes }} min</span><small>{{ ucfirst($meeting->status ?? 'scheduled') }}</small></div>@empty<p class="text-muted">No meeting records yet.</p>@endforelse</section><div class="hm-ref-help"><i class="las la-shield-alt"></i><b>Safety first</b><p>Use a public venue for in-person meetings. Share details only with the intended participants.</p></div></aside></div></div>
@endsection