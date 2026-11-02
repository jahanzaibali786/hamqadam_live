@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="container-fluid py-4">
 <h1>Super Likes</h1><p class="text-muted">Super Likes are a premium appreciation action. They do not automatically send an Interest, Proposal or chat request.</p>
 <div class="row"><section class="col-md-6"><h2 class="h5">Super Likes Sent</h2>
 @forelse($outgoing as $item)<div class="card p-3 mb-2"><a href="{{ route('member_profile',$item->recipient_id) }}">{{ trim(($item->recipient?->first_name??'Member').' '.($item->recipient?->last_name??'')) }}</a><small>{{ $item->coins_charged }} coins · {{ $item->created_at->diffForHumans() }}</small></div>@empty<p>No Super Likes sent yet.</p>@endforelse
 {{ $outgoing->links() }}</section><section class="col-md-6"><h2 class="h5">Super Likes Received</h2>
 @forelse($incoming as $item)<div class="card p-3 mb-2"><span class="badge" style="background:#a84469;color:white">★ Super Like</span><a href="{{ route('member_profile',$item->sender_id) }}">{{ trim(($item->sender?->first_name??'Member').' '.($item->sender?->last_name??'')) }}</a><small>{{ $item->created_at->diffForHumans() }}</small><a href="{{ route('member.workflow.show',$item->sender_id) }}">Send Interest</a></div>@empty<p>No Super Likes received yet.</p>@endforelse
 </section></div>
<section class="card p-3 mt-3"><h2 class="h5">Members Who Liked You</h2>@forelse($likesReceived as $like)<a href="{{ route('member_profile',$like->sender_id) }}">{{ $like->sender?->first_name }} {{ $like->sender?->last_name }} · Like</a>@empty<p>No Likes received yet.</p>@endforelse</section>
</div>
@endsection