@extends('frontend.layouts.member_panel')
@section('panel_content')
<div class="hm-reference hm-parity-ai">
<header class="hm-reference-heading"><div><span class="hm-ref-kicker">Hamqadam AI</span><h1>Hamqadam AI Assistant</h1><p>Your personal guide for a better matchmaking journey.</p></div></header>
<div class="hm-parity-ai-grid">
<aside class="hm-ref-white hm-parity-ai-menu"><h2>Chat with AI Tools</h2>
@foreach([['starters','General Assistant','Conversation ideas','la-comment-dots'],['quality','Profile Improvement','Improve your profile','la-file-alt'],['bio','Generate Bio','Create a better bio','la-pencil-alt'],['scam','Safety Guidance','Check concerning messages','la-shield-alt'],['red_flags','Relationship Advice','Spot possible red flags','la-heart']] as $tool)
<button class="hm-parity-ai-select {{ $loop->first?'selected':'' }}" type="button" data-tool="{{ $tool[0] }}"><i class="las {{ $tool[3] }}"></i><span><b>{{ $tool[1] }}</b><small>{{ $tool[2] }}</small></span></button>
@endforeach
</aside>
<main class="hm-ref-white hm-parity-ai-chat"><div class="hm-ref-section-title"><h2><i class="las la-robot"></i> Guidance &amp; Suggestions</h2></div>
<div class="hm-parity-ai-welcome"><i class="las la-robot"></i><div><strong>Assalam o Alaikum, {{ auth()->user()->first_name }}!</strong><p>Use my profile, conversation and safety tools for practical suggestions. Choose a category or enter context below.</p></div></div>
<div class="hm-parity-ai-suggestions">
@foreach([['starters','Suggest conversation starters'],['bio','Improve my profile bio'],['quality','Analyze my profile'],['red_flags','Review relationship concerns']] as $suggest)
<button type="button" data-tool="{{ $suggest[0] }}"><i class="las la-magic"></i> {{ $suggest[1] }} <i class="las la-angle-right"></i></button>
@endforeach
</div>
@if($errors->any())<div class="alert alert-danger mt-3">{{ $errors->first() }}</div>@endif
<form action="{{ route('member.reference.assistant.run') }}" method="POST" class="hm-parity-ai-compose">@csrf
<label for="hm-ai-tool" class="sr-only">Assistant tool</label><select id="hm-ai-tool" name="tool" class="form-control"><option value="starters">Conversation Starters</option><option value="bio">Improve My Bio</option><option value="quality">Profile Quality</option><option value="scam">Check Suspicious Message</option><option value="red_flags">Check Red Flags</option></select>
<label for="hm-ai-text" class="sr-only">Question or context</label><textarea id="hm-ai-text" name="text" rows="3" maxlength="5000" class="form-control" placeholder="Type your question or context here...">{{ old('text') }}</textarea><button type="submit" class="hm-ref-cta"><i class="las la-paper-plane"></i> Get Guidance</button>
</form></main>
<aside class="hm-parity-ai-right">
<section class="hm-ref-white"><h2>Quick Actions</h2><div class="hm-parity-ai-quick"><a href="{{ route('profile_settings') }}"><i class="las la-user-edit"></i> My Profile</a><a href="{{ route('my_matched_profiles') }}"><i class="las la-heart"></i> Find Matches</a><button type="button" data-tool="bio"><i class="las la-magic"></i> Generate Bio</button><button type="button" data-tool="starters"><i class="las la-comment-dots"></i> Message Ideas</button></div></section>
<section class="hm-ref-white"><h2>Profile Tips</h2><ol class="hm-parity-ai-tips"><li>Add clear profile photos</li><li>Write a meaningful biography</li><li>Complete family information</li><li>Get verified for trust</li><li>Be specific in preferences</li></ol></section>
<section class="hm-ref-white"><h2>Recent AI Results</h2>@forelse($history as $item)<details class="hm-parity-ai-history"><summary>{{ ucwords(str_replace('_',' ',$item->feature)) }} <small>{{ $item->created_at?->diffForHumans() }}</small></summary><pre class="hm-ref-json">{{ json_encode($item->output,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@empty<p class="small text-muted">No saved AI requests yet.</p>@endforelse</section>
</aside></div></div>
<script>
(function(){const root=document.querySelector('.hm-parity-ai');if(!root)return;const select=root.querySelector('#hm-ai-tool');root.querySelectorAll('[data-tool]').forEach(button=>button.addEventListener('click',()=>{select.value=button.dataset.tool;root.querySelectorAll('.hm-parity-ai-select').forEach(el=>el.classList.toggle('selected',el===button));root.querySelector('#hm-ai-text').focus();}));})();
</script>
@endsection