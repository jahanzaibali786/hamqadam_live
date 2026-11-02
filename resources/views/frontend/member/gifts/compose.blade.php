@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="container-fluid py-4 hm-gifts"><a href="{{route('member.gifts.index')}}">← Gift History</a><h1>Send a Gift to {{$recipient->first_name}} {{$recipient->last_name}}</h1>
<p>Current balance: <strong>{{number_format($balance)}} Coins</strong>. The recipient gets 70% of the chosen gift's value.</p>
@if($errors->any())<div class="alert alert-danger">{{$errors->first()}}</div>@endif
@if($gifts->isEmpty())<p>No gifts are currently available.</p>@else
<form action="{{route('member.gifts.send')}}" method="POST" id="hm-gift-send">@csrf
<input type="hidden" name="receiver_id" value="{{$recipient->id}}">
<input type="hidden" name="confirmed_coins" id="hm-confirmed-gift-price" value="">
<div class="row">@foreach($gifts as $gift)<div class="col-sm-6 col-lg-4 mb-3"><label class="card p-3 h-100" style="cursor:pointer"><input type="radio" name="gift_id" value="{{$gift->id}}" data-gift-coins="{{$gift->coins}}" data-gift-name="{{$gift->name}}" required @checked(old('gift_id')==$gift->id)><i class="las la-gift" style="font-size:34px;color:#a84469"></i><strong>{{$gift->name}}</strong><span>{{$gift->coins}} Coins</span></label></div>@endforeach</div>
<label for="hm-gift-message">Personal message (optional)</label><textarea class="form-control" maxlength="300" rows="3" name="message" id="hm-gift-message">{{old('message')}}</textarea>
<p class="mt-3" id="hm-gift-balance" aria-live="polite"></p><button type="submit" class="btn btn-primary mt-2" id="hm-gift-submit">Review & Send Gift</button> <a href="{{route('custom_coins.form')}}">Add Coins</a>
</form>
<script>
(function(){const form=document.getElementById('hm-gift-send');if(!form)return;const balance=@json($balance),recipient=@json(trim($recipient->first_name.' '.$recipient->last_name)),status=document.getElementById('hm-gift-balance'),submit=document.getElementById('hm-gift-submit');function chosen(){return form.querySelector('input[name=gift_id]:checked')}function refresh(){const selection=chosen(),price=Number(selection?.dataset.giftCoins||0);status.textContent=selection?'Cost: '+price+' Coins. Remaining after gift: '+(balance-price)+' Coins.':'';submit.disabled=!selection||price>balance;}form.addEventListener('change',refresh);refresh();form.addEventListener('submit',e=>{const radio=chosen();if(!radio){e.preventDefault();return}const price=Number(radio.dataset.giftCoins);if(price>balance){e.preventDefault();alert('Insufficient coins. Please add coins.');return}const text='Send '+radio.dataset.giftName+' to '+recipient+' for '+price+' Coins? Available: '+balance+' Coins. Recipient receives '+Math.floor(price*.7)+' Coins.';if(!confirm(text)){e.preventDefault();return}document.getElementById('hm-confirmed-gift-price').value=price;submit.disabled=true;});})();
</script>@endif</div>
@endsection