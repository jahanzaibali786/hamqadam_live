@php
    $chatItems = is_iterable($chats) ? collect($chats) : collect();
@endphp
@foreach ($chatItems->reverse() as $chat)
    @if ((int) $chat->sender_user_id === (int) Auth::id())
        @include('frontend.member.messages.messages_right_single', ['chat' => $chat])
    @else
        @include('frontend.member.messages.messages_left_single', ['chats' => collect([$chat])])
    @endif
@endforeach