@extends('frontend.layouts.app')
@section('content')
<section class="hq-member-panel-page py-5">
	<div class="container">
		<div class="d-flex align-items-start">
			@include('frontend.member.sidebar')
			<div class="aiz-user-panel overflow-hidden">
				@yield('panel_content')
			</div>
		</div>
	</div>
</section>
@endsection

