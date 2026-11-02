@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="container-fluid py-4 hm-gifts"><h1><i class="las la-gift"></i> Virtual Gifts</h1><p>Send gifts using your existing wallet coins. The recipient receives 70% of the coin value.</p>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<div class="card p-3 mb-3"><b>Available coins: {{number_format($balance)}}</b> <a href="{{route('custom_coins.form')}}">Add Coins</a></div>
<h2 class="h5">Available Gifts</h2><div class="row">
@forelse($gifts as $gift)<div class="col-md-4 col-lg-3 mb-3"><div class="card p-3 h-100"><i class="las la-gift" style="font-size:34px;color:#a84469"></i><b>{{$gift->name}}</b><span>{{$gift->coins}} Coins</span><small>Select a member profile and choose Send Gift to deliver this gift.</small></div></div>@empty<p>No active gifts available. Please contact support.</p>@endforelse
</div><h2 class="h5 mt-3">Gifts Sent</h2>
@forelse($sent as $item)<div class="card p-3 mb-2"><a href="{{route('member.gifts.transaction',$item->id)}}">{{$item->gift?->name}} to {{$item->receiver?->first_name}} — {{$item->coins}} coins</a><small>{{$item->created_at?->format('d M Y')}}</small></div>@empty<p>No gifts sent.</p>@endforelse {{$sent->links()}}
<h2 class="h5 mt-4">Gifts Received</h2>
@forelse($received as $item)<div class="card p-3 mb-2"><a href="{{route('member.gifts.transaction',$item->id)}}">{{$item->gift?->name}} from {{$item->sender?->first_name}}</a><small>{{$item->created_at?->format('d M Y')}}</small></div>@empty<p>No gifts received.</p>@endforelse {{$received->links()}}
</div>
@endsection