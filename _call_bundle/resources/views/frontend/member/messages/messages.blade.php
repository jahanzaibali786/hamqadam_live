<div class="chat-box-wrap h-100">

    <div class="attached-top bg-white border-bottom chat-header d-flex justify-content-between align-items-center p-3 shadow-sm">

        <div class="media align-items-center">

            <span class="avatar avatar-sm mr-3 flex-shrink-0">

              @php

                  $current_user = Auth::user()->id;

              @endphp

                @if($current_user == $chat_thread->sender->id)

                  @php $user_to_show = 'receiver';  @endphp

                @else

                  @php $user_to_show = 'sender';  @endphp

                @endif



                @if ($chat_thread->$user_to_show->photo != null)

                    <img src="{{ uploaded_asset($chat_thread->$user_to_show->photo) }}">

                @else

                    <img src="{{ static_asset('assets/img/avatar-place.png') }}">

                @endif

            </span>

            <div class="media-body">

                <h6 class="fs-15 mb-1">

                    {{ $chat_thread->$user_to_show->first_name.' '.$chat_thread->$user_to_show->last_name }}

                    @if(Cache::has('user-is-online-' . $chat_thread->$user_to_show->id))

                        <span class="badge badge-dot badge-success badge-circle"></span>

                    @else

                        <span class="badge badge-dot badge-secondary badge-circle"></span>

                    @endif

                </h6>

            </div>

        </div>

        <div class="d-flex align-items-center">

            <button type="button" class="btn btn-icon btn-circle btn-soft-success mr-2" onclick="startCall('audio')" title="{{ translate('Audio Call') }}" {{ !empty($chat_is_blocked) ? 'disabled' : '' }}>

                <i class="las la-phone"></i>

            </button>

            <button type="button" class="btn btn-icon btn-circle btn-soft-info mr-2" onclick="startCall('video')" title="{{ translate('Video Call') }}" {{ !empty($chat_is_blocked) ? 'disabled' : '' }}>

                <i class="las la-video"></i>

            </button>

            <button class="aiz-mobile-toggler d-lg-none aiz-all-chat-toggler mr-2" data-toggle="class-toggle" data-target=".chat-user-list-wrap">

                <span></span>

            </button>

            <div class="dropdown mr-2">

                <button class="btn btn-icon btn-circle btn-soft-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">

                    <i class="las la-ellipsis-v"></i>

                </button>

                <div class="dropdown-menu dropdown-menu-right shadow-sm">

                    @if (!empty($chat_blocked_by_me))

                        <a class="dropdown-item" href="javascript:void(0)" onclick="unblockChatThread()">

                            <i class="las la-unlock mr-2"></i>{{ translate("Unblock Chat") }}

                        </a>

                    @elseif (!empty($chat_blocked_by_other))

                        <span class="dropdown-item text-muted">{{ translate("Blocked by the other member") }}</span>

                    @else

                        <a class="dropdown-item" href="javascript:void(0)" onclick="blockChatThread()">

                            <i class="las la-ban mr-2"></i>{{ translate("Block Chat") }}

                        </a>

                    @endif

                    <a class="dropdown-item" href="javascript:void(0)" onclick="clearChatThread()">

                        <i class="las la-broom mr-2"></i>{{ translate("Clear This Chat") }}

                    </a>

                    <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="openChatReportModal()">

                        <i class="las la-flag mr-2"></i>{{ translate("Report Chat") }}

                    </a>

                </div>

            </div>

            <button class="btn btn-icon btn-circle btn-soft-primary chat-info" data-toggle="class-toggle" data-target=".chat-info-wrap"><i class="las la-info-circle"></i></button>

        </div>

    </div>

    <div class="chat-list-wrap c-scrollbar-light scroll-to-btm" id="parentDiv">

        @if (count($chats) > 0)

            <div class="chat-coversation-load text-center">

                <button class="btn btn-link load-more-btn" data-first="{{ $chats->last()->id }}" type="button">{{ translate('Load More') }}</button>

            </div>

        @endif

        <div class="chat-list px-4" id="chat-messages">

            @include('frontend.member.messages.messages_part',['chats' => $chats])

            @include('frontend.member.messages.calls_part', ['calls' => $call_logs ?? collect()])

        </div>

    </div>

    <div class="chat-footer border-top p-3 attached-bottom bg-white">

        @if (!empty($chat_blocked_by_me))

            <div class="alert alert-warning mb-3">

                {{ translate("You blocked this chat. Unblock it to send messages again.") }}

            </div>

        @elseif (!empty($chat_blocked_by_other))

            <div class="alert alert-warning mb-3">

                {{ translate("This chat is blocked by the other member.") }}

            </div>

        @endif

        <form id="send-mesaage">

            <div class="input-group">

                <input type="hidden" id="chat_thread_id" name="chat_thread_id" value="{{ $chat_thread->id }}">

                <input type="text" class="form-control" name="message" id="message" placeholder="Your Message.." autocomplete="off" {{ !empty($chat_is_blocked) ? "disabled" : "" }}>

                <input type="hidden" class="" name="attachment" id="attachment">

                <div class="input-group-append">

                    <button class="btn btn-circle btn-icon chat-attachment" type="button" {{ !empty($chat_is_blocked) ? "disabled" : "" }}>

                        <i class="las la-paperclip"></i>

                    </button>

                    <button class="btn btn-primary btn-circle btn-icon" onclick="send_reply()" type="button" {{ !empty($chat_is_blocked) ? "disabled" : "" }}>

                        <i class="las la-paper-plane"></i>

                    </button>

                </div>

            </div>

        </form>

    </div>

    <div class="chat-info-wrap">

        <div class="overlay dark c-pointer" data-toggle="class-toggle" data-target=".chat-info-wrap" data-same=".chat-info"></div>

          <div class="chat-info c-scrollbar-light p-4 z-1">

                <div class="px-4 text-center mb-3">

                    <span class="avatar avatar-md mb-3">

                        @if ($chat_thread->$user_to_show->photo != null)

                            <img src="{{ uploaded_asset($chat_thread->$user_to_show->photo) }}">

                        @else

                            <img src="{{ static_asset('assets/img/avatar-place.png') }}">

                        @endif

                    </span>

                    <h4 class="h5 mb-2 fw-600">{{ $chat_thread->$user_to_show->first_name.' '.$chat_thread->$user_to_show->last_name }}</h4>

                </div>

                <div class="text-center">

                    <h6 class="fs-13">{{ translate('Age') }}: {{ \Carbon\Carbon::parse($chat_thread->$user_to_show->member->birthday)->age }}</h6>

                    <h6 class="fs-13">

                        {{ translate('Height') }} :

                        @if(!empty( $chat_thread->$user_to_show->physical_attributes->height))

                            {{ $chat_thread->$user_to_show->physical_attributes->height }}

                        @endif

                    </h6>

                    @if(get_setting('member_spiritual_and_social_background_section') == 'on')

                        <h6 class="fs-13">

                            {{ translate('Religion') }} :

                          @if(!empty($chat_thread->$user_to_show->spiritual_backgrounds->religion_id))

                              {{ $chat_thread->$user_to_show->spiritual_backgrounds->religion->name }}

                          @endif

                        </h6>

                    @endif

                    @if(get_setting('member_present_address_section') == 'on')

                    <h6 class="fs-13">

                        {{ translate('Location') }} :

                      @php

                          $present_address = \App\Models\Address::where('type','present')->where('user_id', $chat_thread->$user_to_show->id)->first();

                      @endphp

                      @if(!empty($present_address->country_id))

                          {{ $present_address->country->name }}

                      @endif

                    </h6>

                    @endif

                    @if(get_setting('member_language_section') == 'on')

                        <h6 class="fs-13">

                            {{ translate('Mother Language') }} :

                          @if($chat_thread->$user_to_show->member->mothere_tongue != null)

                              {{ \App\Models\MemberLanguage::where('id',$chat_thread->$user_to_show->member->mothere_tongue)->first()->name }}

                          @endif

                        </h6>

                    @endif



                    <div class="text-center mb-3 px-3 mt-3">

                        <a

                            @if(get_setting('full_profile_show_according_to_membership') == 1 && Auth::user()->membership == 1)

                                href="javascript:void(0);" onclick="package_update_alert()"

                            @else

                                href="{{ route('member_profile', $chat_thread->$user_to_show->id) }}"

                            @endif

                            class="btn btn-block btn-soft-primary">{{ translate('View Full Profile') }}

                        </a>

                    </div>

                </div>

            </div>

    </div>

</div>





<style>

    .call-shell-avatar,

    .call-audio-avatar {

        width: 96px;

        height: 96px;

        border-radius: 50%;

        overflow: hidden;

        margin: 0 auto;

        box-shadow: 0 0 0 8px rgba(255, 52, 132, 0.15), 0 16px 48px rgba(255, 52, 132, 0.24);

    }

    .call-shell-avatar img,

    .call-audio-avatar img {

        width: 100%;

        height: 100%;

        object-fit: cover;

    }

    .call-shell {

        background: linear-gradient(180deg, #fff 0%, #fff7fb 100%);

    }

    .call-ringing-pill {

        display: inline-flex;

        align-items: center;

        gap: 8px;

        padding: 8px 14px;

        border-radius: 999px;

        background: #fff0f6;

        color: #ff2f84;

        font-weight: 600;

        position: relative;

        overflow: hidden;

    }

    .call-ringing-pill::before {

        content: '';

        width: 10px;

        height: 10px;

        border-radius: 50%;

        background: #ff2f84;

        box-shadow: 0 0 0 0 rgba(255, 47, 132, 0.35);

        animation: callPulse 1.4s infinite;

    }

    @keyframes callPulse {

        0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 47, 132, 0.35); }

        70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(255, 47, 132, 0); }

        100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(255, 47, 132, 0); }

    }

    .call-screen-modal-content,

    .call-screen {

        border-radius: 24px;

        overflow: hidden;

    }

    .call-screen {

        min-height: 72vh;

    }

    .call-video-area,

    .call-audio-area {

        position: absolute;

        inset: 0;

    }

    .call-audio-area {

        display: flex;

        flex-direction: column;

        justify-content: center;

        align-items: center;

        background: radial-gradient(circle at top, rgba(255,255,255,.08), rgba(0,0,0,.35));

    }

    .call-overlay-header {

        position: relative;

        z-index: 3;

        background: linear-gradient(180deg, rgba(0,0,0,.68), rgba(0,0,0,0));

    }

    .call-controls {

        position: absolute;

        left: 0;

        right: 0;

        bottom: 0;

        z-index: 4;

    }

    .call-controls .btn {

        width: 52px;

        height: 52px;

    }

    .local-video-preview {

        position: absolute;

        right: 18px;

        bottom: 98px;

        width: 140px;

        height: 190px;

        border-radius: 18px;

        overflow: hidden;

        border: 2px solid rgba(255,255,255,.6);

        box-shadow: 0 10px 30px rgba(0,0,0,.35);

        z-index: 3;

        background: #111;

    }

    .remote-video-screen {

        width: 100%;

        height: 100%;

        background: #111;

    }

    .call-log-item .call-log-card {

        border-radius: 16px;

        background: linear-gradient(180deg, #fff 0%, #fff4f9 100%);

        border: 1px solid rgba(255, 47, 132, 0.1);

        padding: 12px 14px;

    }

    .call-log-icon {

        display: inline-flex;

        width: 32px;

        height: 32px;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background: #fff0f6;

        color: #ff2f84;

    }

</style>





<div class="modal fade" id="call-action-modal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg">

        <div class="modal-content border-0 overflow-hidden" style="border-radius: 20px;">

            <div class="modal-body p-0">

                <div class="call-shell p-4 p-lg-5 text-center">

                    <div class="call-shell-avatar mx-auto mb-3">

                        <img id="call-shell-avatar" src="{{ static_asset('assets/img/avatar-place.png') }}" alt="avatar">

                    </div>

                    <h4 class="mb-1 fw-600" id="call-shell-name">{{ translate('Calling') }}</h4>

                    <p class="mb-3 text-muted" id="call-shell-status">{{ translate('Connecting...') }}</p>

                    <div class="mb-4" id="call-shell-meta"></div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">

                        <button type="button" class="btn btn-soft-danger btn-lg px-4 d-none" id="call-shell-decline" onclick="console.log('[chat call] decline button clicked', window.currentCallId); declineIncomingCall()">{{ translate('Decline') }}</button>

                        <button type="button" class="btn btn-primary btn-lg px-4 d-none" id="call-shell-accept" onclick="console.log('[chat call] accept button clicked', window.currentCallId); acceptIncomingCall()">{{ translate('Accept') }}</button>

                        <button type="button" class="btn btn-warning btn-lg px-4 d-none" id="call-shell-cancel" onclick="console.log('[chat call] cancel button clicked', window.currentCallId); cancelOutgoingCall()">{{ translate('Cancel Call') }}</button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>



<div class="modal fade" id="call-screen-modal" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">

    <div class="modal-dialog modal-dialog-centered modal-xl call-screen-modal-dialog">

        <div class="modal-content call-screen-modal-content border-0">

            <div class="modal-body p-0">

                <div class="call-screen bg-dark text-white position-relative overflow-hidden">

                    <div id="call-video-area" class="call-video-area d-none"></div>

                    <div id="call-audio-area" class="call-audio-area text-center p-4 p-lg-5"></div>

                    <div class="call-overlay-header d-flex justify-content-between align-items-center px-3 px-lg-4 py-3">

                        <div>

                            <h5 class="mb-0 fw-600" id="call-screen-name">{{ translate('Call') }}</h5>

                            <small class="text-white-50" id="call-screen-status">{{ translate('Connecting...') }}</small>

                        </div>

                        <div class="text-right">

                            <div class="call-timer fs-18 fw-600" id="call-screen-timer">00:00</div>

                        </div>

                    </div>

                    <div class="call-controls d-flex justify-content-center align-items-center flex-wrap gap-2 px-3 pb-4">

                        <button type="button" class="btn btn-icon btn-circle btn-light" id="call-toggle-mic" onclick="toggleCallMic()"><i class="las la-microphone"></i></button>

                        <button type="button" class="btn btn-icon btn-circle btn-light d-none" id="call-toggle-camera" onclick="toggleCallCamera()"><i class="las la-video"></i></button>

                        <button type="button" class="btn btn-icon btn-circle btn-light d-none" id="call-switch-camera" onclick="switchCallCamera()"><i class="las la-sync-alt"></i></button>

                        <button type="button" class="btn btn-danger btn-icon btn-circle" id="call-end-btn" onclick="endActiveCall()"><i class="las la-phone-slash"></i></button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>





<style>

    .call-shell-avatar,

    .call-audio-avatar {

        width: 96px;

        height: 96px;

        border-radius: 50%;

        overflow: hidden;

        margin: 0 auto;

        box-shadow: 0 0 0 8px rgba(255, 52, 132, 0.15), 0 16px 48px rgba(255, 52, 132, 0.24);

    }

    .call-shell-avatar img,

    .call-audio-avatar img {

        width: 100%;

        height: 100%;

        object-fit: cover;

    }

    .call-shell {

        background: linear-gradient(180deg, #fff 0%, #fff7fb 100%);

    }

    .call-ringing-pill {

        display: inline-flex;

        align-items: center;

        gap: 8px;

        padding: 8px 14px;

        border-radius: 999px;

        background: #fff0f6;

        color: #ff2f84;

        font-weight: 600;

    }

    .call-screen-modal-content,

    .call-screen {

        border-radius: 24px;

        overflow: hidden;

    }

    .call-screen {

        min-height: 72vh;

    }

    .call-video-area,

    .call-audio-area {

        position: absolute;

        inset: 0;

    }

    .call-audio-area {

        display: flex;

        flex-direction: column;

        justify-content: center;

        align-items: center;

        background: radial-gradient(circle at top, rgba(255,255,255,.08), rgba(0,0,0,.35));

    }

    .call-overlay-header {

        position: relative;

        z-index: 3;

        background: linear-gradient(180deg, rgba(0,0,0,.68), rgba(0,0,0,0));

    }

    .call-controls {

        position: absolute;

        left: 0;

        right: 0;

        bottom: 0;

        z-index: 4;

    }

    .call-controls .btn {

        width: 52px;

        height: 52px;

    }

    .local-video-preview {

        position: absolute;

        right: 18px;

        bottom: 98px;

        width: 140px;

        height: 190px;

        border-radius: 18px;

        overflow: hidden;

        border: 2px solid rgba(255,255,255,.6);

        box-shadow: 0 10px 30px rgba(0,0,0,.35);

        z-index: 3;

        background: #111;

    }

    .remote-video-screen {

        width: 100%;

        height: 100%;

        background: #111;

    }

    .call-log-item .call-log-card {

        border-radius: 16px;

        background: linear-gradient(180deg, #fff 0%, #fff4f9 100%);

        border: 1px solid rgba(255, 47, 132, 0.1);

        padding: 12px 14px;

    }

    .call-log-icon {

        display: inline-flex;

        width: 32px;

        height: 32px;

        align-items: center;

        justify-content: center;

        border-radius: 50%;

        background: #fff0f6;

        color: #ff2f84;

    }

</style>



@section('script')

    <script type="text/javascript">

        window.initChatRealtimeRefresh = function () {

            const threadId = $('#chat_thread_id').val();

            if (!threadId) {

                console.warn('[chat realtime] threadId missing');

                return;

            }



            $.get(window.location.href, {}, function (data) {

                const wrapper = $(data).find('#single_chat').html();

                if (wrapper) {

                    $('#single_chat').html(wrapper);

                    AIZ.extra.scrollToBottom();

                    initializeLoadMore();

                    bindChatSend();

                    initChatThreadRealtime();

                }

            });

        };



        function bindChatSend(){

            $('#send-mesaage').off('submit').on('submit', function(e){

                e.preventDefault();

                send_reply();

            });

        }



        function loadChats(el){

            $('.selected-chat').each(function() {

                $(this).removeClass('bg-soft-primary');

                $(this).removeClass('selected-chat');

            });

            $(el).addClass('selected-chat');

            $(el).addClass('bg-soft-primary');

            $.get($(el).data('url'),{}, function(data){

                $('#single_chat').html(data);

                AIZ.extra.scrollToBottom();

                initializeLoadMore();

                bindChatSend();

                initChatThreadRealtime();

            });

        }

        function send_reply(){

            console.log('[chat realtime] send_reply clicked');

            var chat_thread_id = $('#chat_thread_id').val();

            var message = $('#message').val();

            var attachment = $('#attachment').val();

            if(message.length > 0 || attachment.length > 0){

                $.post('{{ route('chat.reply') }}',{_token:'{{ csrf_token() }}', chat_thread_id:chat_thread_id, message:message, attachment:attachment}, function(data){

                    $('#message').val('');

                    $('#attachment').val('');

                    $('#chat-messages').append(data);

                    AIZ.extra.scrollToBottom();

                });

            }

        }

        $(document).on('click','.chat-attachment',function(){

            AIZ.uploader.trigger(

                this,

                'direct',

                'all',

                '',

                true,

                function(files){

                    $('#attachment').val(files);

                    send_reply();

                }

            );

        });



        function initChatThreadRealtime() {

            console.log('[chat realtime] initChatThreadRealtime called');

            if (typeof window.Echo === 'undefined' || !$('#chat_thread_id').length) {

                return;

            }



            const threadId = $('#chat_thread_id').val();

            if (!threadId) {

                console.warn('[chat realtime] threadId missing');

                return;

            }



            window.Echo.leave(`chat-thread.${threadId}`);

            window.Echo.private(`chat-thread.${threadId}`)

                .listen('.message-sent', function (event) {

                    console.log('[chat realtime] thread message-sent raw', event);

                    var payload = normalizeChatEvent(event);

                    console.log('[chat realtime] thread message-sent payload', payload);

                    if (payload.sender && parseInt(payload.sender.id) === {{ Auth::id() }}) {

                        return;

                    }

                    if (payload.message || (payload.attachments && payload.attachments.length)) {

                        console.log('[chat realtime] appending incoming message', payload);

                        appendIncomingChatMessage(payload);

                    } else {

                        console.warn('[chat realtime] empty payload, falling back to refresh', payload);

                        window.initChatRealtimeRefresh();

                    }

                    if (typeof checkUnreadChats === 'function') {

                        checkUnreadChats();

                    }

                })

                .listen('.message-read', function () {

                    if (typeof checkUnreadChats === 'function') {

                        checkUnreadChats();

                    }

                })

                .listen('.typing-indicator', function (event) {

                    updateTypingIndicator(event.is_typing && event.user ? event.user.name : '');

                });

        }



        function normalizeChatEvent(event) {

            if (!event) {

                return {};

            }



            if (event.data && typeof event.data === 'object') {

                return event.data;

            }



            if (event.message || event.sender || event.attachments) {

                return event;

            }



            return event.payload && typeof event.payload === 'object' ? event.payload : event;

        }

        function appendIncomingChatMessage(event) {

            console.log('[chat realtime] appendIncomingChatMessage input', event);

            var avatar = event.sender && event.sender.photo ? event.sender.photo : '{{ static_asset('assets/img/avatar-place.png') }}';

            var html = '';



            if (event.message) {

                html += '<div class="chat-coversation"><div class="media"><span class="avatar avatar-xs flex-shrink-0"><img src="' + avatar + '"></span><div class="media-body"><div class="text">' + escapeHtml(event.message) + '</div><span class="time">Just now</span></div></div></div>';

            }



            if (event.attachments && event.attachments.length) {

                html += '<div class="chat-coversation"><div class="media"><span class="avatar avatar-xs flex-shrink-0"><img src="' + avatar + '"></span><div class="media-body"><div class="file-preview box sm">';



                event.attachments.forEach(function (attachment) {

                    var downloadUrl = attachment.download_url || attachment.url || '#';

                    var originalName = attachment.original_name || attachment.name || 'Attachment';

                    var extension = attachment.extension ? '.' + attachment.extension : '';

                    var sizeLabel = formatAttachmentSize(attachment.size);



                    if (attachment.type === 'image') {

                        html += '<div class="mb-2 file-preview-item" title="' + escapeHtml(originalName) + '"><a href="' + escapeHtml(safeDownloadUrl) + '" target="_blank" rel="noopener" class="' + attachmentClass + '" data-attachment-id="' + attachmentId + '" data-download-url="' + escapeHtml(safeDownloadUrl) + '"><div class="thumb"><img src="' + attachmentPreviewUrl + '" class="img-fit"></div><div class="body"><h6 class="d-flex"><span class="text-truncate title">' + escapeHtml(originalName) + '</span><span class="ext">' + escapeHtml(extension) + '</span></h6><p>' + escapeHtml(sizeLabel) + '</p></div></a></div>';

                    } else {

                        html += '<div class="mb-2 file-preview-item" title="' + escapeHtml(originalName) + '"><a href="' + escapeHtml(safeDownloadUrl) + '" target="_blank" rel="noopener" class="' + attachmentClass + '" data-attachment-id="' + attachmentId + '" data-download-url="' + escapeHtml(safeDownloadUrl) + '"><div class="thumb"><i class="la la-file-text"></i></div><div class="body"><h6 class="d-flex"><span class="text-truncate title">' + escapeHtml(originalName) + '</span><span class="ext">' + escapeHtml(extension) + '</span></h6><p>' + escapeHtml(sizeLabel) + '</p></div></a></div>';

                    }

                });



                html += '</div><span class="time">Just now</span></div></div></div>';

            }



            if (html) {

                console.log('[chat realtime] append html length', html.length);

                $('#chat-messages').append(html);

                AIZ.extra.scrollToBottom();

            }

        }



        function formatAttachmentSize(size) {

            var bytes = parseInt(size || 0, 10);

            if (!bytes || isNaN(bytes) || bytes < 1) {

                return 'File';

            }



            var units = ['B', 'KB', 'MB', 'GB'];

            var unitIndex = 0;

            var value = bytes;



            while (value >= 1024 && unitIndex < units.length - 1) {

                value = value / 1024;

                unitIndex++;

            }



            return (unitIndex === 0 ? value.toFixed(0) : value.toFixed(1)) + ' ' + units[unitIndex];

        }



        function escapeHtml(value) {

            return String(value || '').replace(/[&<>"']/g, function (m) {

                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];

            });

        }

        function updateTypingIndicator(label) {

            let indicator = $('#typing-indicator');

            if (!indicator.length) {

                $('#chat-messages').after('<div id="typing-indicator" class="px-4 py-2 fs-12 text-muted"></div>');

                indicator = $('#typing-indicator');

            }

            indicator.text(label ? (label + ' is typing...') : '');

        }



        $(document).ready(function () {

            bindChatSend();

            initChatThreadRealtime();

        });



        function initializeLoadMore(){

            $('.load-more-btn').on('click', function(){

                $.post('{{ route('get-old-message') }}', {_token:'{{ csrf_token() }}', first_message_id:$(this).data('first')}, function(data){

                    if (data.first_message_id > 0) {

                        $('#chat-messages').prepend(data.messages);

                        $('.load-more-btn').data('first', data.first_message_id);

                    }

                });

            });

        }



        function package_update_alert(){

          $('.package_update_alert_modal').modal('show');

        }

    </script>



<script src="https://download.agora.io/sdk/release/AgoraRTC_N-4.24.0.js"></script>

<script type="text/javascript">

    window.currentCallState = null;
    window.currentUserId = {{ Auth::id() }};

    window.currentCallTracks = [];

    window.currentCallClient = null;

    window.currentCallTimer = null;

    window.currentCallDurationTimer = null;

    window.currentCallStartedAt = null;

    window.setCallButtonsDisabled = function (disabled) {

        $('[onclick="startCall(\'audio\')"], [onclick="startCall(\'video\')"]').prop('disabled', !!disabled).toggleClass('disabled', !!disabled);

    };



    function getCallCounterpart(call) {
        var userId = parseInt(window.currentUserId || {{ Auth::id() }}, 10);
        var callerId = call && call.caller && call.caller.id ? parseInt(call.caller.id, 10) : null;
        var receiverId = call && call.receiver && call.receiver.id ? parseInt(call.receiver.id, 10) : null;
        if (callerId && userId && callerId === userId) {
            return call.receiver || null;
        }
        if (receiverId && userId && receiverId === userId) {
            return call.caller || null;
        }
        return call.receiver || call.caller || null;
    }

    function callRequest(url, data, onSuccess) {

        return $.ajax({

            url: url,

            method: 'POST',

            data: Object.assign({_token: '{{ csrf_token() }}'}, data || {}),

            success: onSuccess,

            error: function(xhr) {

                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ translate('Calling service unavailable.') }}';

                AIZ.plugins.notify('danger', message);

            }

        });

    }



    function startCall(type) {

        if (!$('#chat_thread_id').length) { return; }

        callRequest('{{ route('chat.call.start') }}', {

            chat_thread_id: $('#chat_thread_id').val(),

            call_type: type

        }, function(response) {

            if (!response.success) {

                AIZ.plugins.notify('danger', response.message || '{{ translate('Calling service unavailable.') }}');

                return;

            }

            window.currentCallState = response.data.call;

            window.currentCallState.rtc = response.data.rtc;

            showOutgoingCallModal(window.currentCallState);

            setCallButtonsDisabled(true);

            startIncomingCallCountdown(window.currentCallState);

            joinAgoraCall(window.currentCallState, response.data.rtc, true);

        });

    }



    function showOutgoingCallModal(call) {
        console.log('[chat call] showOutgoingCallModal', call);
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        window.currentCallId = call && call.id ? call.id : null;
        $('#call-action-modal').attr('data-call-id', call && call.id ? call.id : null);

        $('#call-shell-avatar').attr('src', call.receiver && call.receiver.photo ? call.receiver.photo : '{{ static_asset('assets/img/avatar-place.png') }}');

        $('#call-shell-name').text(call.receiver && call.receiver.name ? call.receiver.name : '{{ translate('Calling') }}');

        $('#call-shell-status').text('{{ translate('Calling...') }}');

        $('#call-shell-meta').html('<span class="badge badge-light">' + (call.call_type || 'audio') + '</span>');

        $('#call-shell-accept').addClass('d-none').removeData('call-id');

        $('#call-shell-decline').addClass('d-none').removeData('call-id');

        $('#call-action-modal').attr('data-call-id', call && call.id ? call.id : null);
        $('#call-shell-cancel').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call && call.id ? call.id : null);

        $('#call-action-modal').modal('show');

    }



    function showIncomingCallModal(call) {
        console.log('[chat call] showIncomingCallModal', call);

        window.currentCallState = call;

        $('#call-shell-avatar').attr('src', call.caller && call.caller.photo ? call.caller.photo : '{{ static_asset('assets/img/avatar-place.png') }}');

        $('#call-shell-name').text(call.caller && call.caller.name ? call.caller.name : '{{ translate('Incoming Call') }}');

        $('#call-shell-status').text(call.call_type === 'video' ? '{{ translate('Incoming Video Call') }}' : '{{ translate('Incoming Audio Call') }}');

        $('#call-shell-meta').html('<div class="call-ringing-pill">{{ translate('Ringing') }}</div>');

        $('#call-action-modal').attr('data-call-id', call && call.id ? call.id : null);
        $('#call-shell-accept').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call.id);

        $('#call-shell-decline').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call.id);

        $('#call-shell-cancel').addClass('d-none');

        $('#call-action-modal').modal('show');

        setCallButtonsDisabled(true);

        startIncomingCallCountdown(call);

    }



    function acceptIncomingCall() {
        console.log('[chat call] acceptIncomingCall entered', window.currentCallId, $('#call-action-modal').attr('data-call-id'), $('#call-shell-accept').attr('data-call-id'), $('#call-shell-accept').data('call-id'));

        var callId = window.currentCallId || $('#call-action-modal').attr('data-call-id') || $('#call-shell-accept').attr('data-call-id') || $('#call-shell-accept').data('call-id');

        if (!callId) { return; }

        callRequest('{{ route('chat.call.accept', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function(response) {

            if (!response.success) {

                AIZ.plugins.notify('danger', response.message || '{{ translate('Calling service unavailable.') }}');

                return;

            }

            $('#call-action-modal').modal('hide');

            window.currentCallState = response.data.call;

            window.currentCallState.rtc = response.data.rtc;

            joinAgoraCall(window.currentCallState, response.data.rtc, false);
            if (typeof openActiveCallScreen === 'function') {
                openActiveCallScreen(window.currentCallState);
            }

        });

    }



    function declineIncomingCall() {
        console.log('[chat call] declineIncomingCall entered', window.currentCallId, $('#call-action-modal').attr('data-call-id'), $('#call-shell-decline').attr('data-call-id'), $('#call-shell-decline').data('call-id'));

        var callId = window.currentCallId || $('#call-action-modal').attr('data-call-id') || $('#call-shell-decline').attr('data-call-id') || $('#call-shell-decline').data('call-id');

        if (!callId) { return; }

        callRequest('{{ route('chat.call.reject', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function(response) {

            $('#call-action-modal').modal('hide');

            stopCallSession();

            appendCallTimelineEntry(response.data.call);

        });

    }



    function cancelOutgoingCall() {
        console.log('[chat call] cancelOutgoingCall entered', window.currentCallId, $('#call-action-modal').attr('data-call-id'), $('#call-shell-cancel').attr('data-call-id'), $('#call-shell-cancel').data('call-id'), window.currentCallState);

        var callId = window.currentCallId || $('#call-action-modal').attr('data-call-id') || $('#call-shell-cancel').attr('data-call-id') || $('#call-shell-cancel').data('call-id') || (window.currentCallState && window.currentCallState.id ? window.currentCallState.id : null);

        if (!callId) { return; }

        callRequest('{{ route('chat.call.cancel', ['call' => '__CALL__']) }}'.replace('__CALL__', callId), {}, function(response) {

            $('#call-action-modal').modal('hide');

            stopCallSession();

            appendCallTimelineEntry(response.data.call);

        });

    }



    function openActiveCallScreen(call) {

        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        $('#call-screen-name').text(peer.name ? peer.name : '{{ translate('Call') }}');

        $('#call-screen-status').text('{{ translate('Connected') }}');

        $('#call-screen-timer').text('00:00');

        if (call.call_type === 'video') {

            $('#call-video-area').removeClass('d-none');

            $('#call-audio-area').addClass('d-none');

            $('#call-toggle-camera, #call-switch-camera').removeClass('d-none');

        } else {

            $('#call-video-area').addClass('d-none');

            $('#call-audio-area').removeClass('d-none');

            $('#call-toggle-camera, #call-switch-camera').addClass('d-none');

            var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
            $('#call-audio-area').html('<div class="call-audio-avatar mb-3"><img src="' + (peer.photo ? peer.photo : '{{ static_asset('assets/img/avatar-place.png') }}') + '" alt="avatar"></div><h3 class="mb-0">' + (peer.name ? peer.name : '') + '</h3><p class="mb-0 text-white-50">{{ translate('Connected') }}</p>');

        }

        clearTimeout(window.currentCallTimer);

        $('#call-screen-modal').modal('show');

        setCallButtonsDisabled(true);

        startCallTimer();

    }



    function endActiveCall(status) {

        if (!window.currentCallState || !window.currentCallState.id) { return; }

        callRequest('{{ route('chat.call.end', ['call' => '__CALL__']) }}'.replace('__CALL__', window.currentCallState.id), { status: status || 'ended' }, function(response) {

            $('#call-screen-modal').modal('hide');

            stopCallSession();

            appendCallTimelineEntry(response.data.call);

        });

    }



    function toggleCallMic() {

        var track = window.currentCallTracks.find(function(item) { return item && item.trackMediaType === 'audio'; });

        if (track) {

            track.setEnabled(!track.enabled);

        }

    }



    function toggleCallCamera() {

        var track = window.currentCallTracks.find(function(item) { return item && item.trackMediaType === 'video'; });

        if (track) {

            track.setEnabled(!track.enabled);

        }

    }



    function switchCallCamera() {

        var track = window.currentCallTracks.find(function(item) { return item && item.trackMediaType === 'video'; });

        if (!track || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return; }

        navigator.mediaDevices.enumerateDevices().then(function(devices) {

            var cameras = devices.filter(function(device) { return device.kind === 'videoinput'; });

            if (cameras.length > 1 && track.setDevice) {

                track.setDevice(cameras[1].deviceId);

            }

        });

    }



    async function joinAgoraCall(call, rtc, isCaller) {

        if (typeof AgoraRTC === 'undefined' || !rtc || !rtc.token) {

            AIZ.plugins.notify('danger', '{{ translate('Calling service unavailable.') }}');

            return;

        }

        if (window.currentCallClient || (window.currentCallTracks && window.currentCallTracks.length)) {

            stopCallSession(false);

        }

        window.currentCallClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

        window.currentCallClient.on('user-published', async function(user, mediaType) {

            await window.currentCallClient.subscribe(user, mediaType);

            if (mediaType === 'video') {

                $('#call-video-area').removeClass('d-none').html('<div id="remote-video-area" class="remote-video-screen"></div><div id="local-video-preview" class="local-video-preview"></div>');

                if (user.videoTrack) { user.videoTrack.play('remote-video-area'); }

            }

            if (mediaType === 'audio' && user.audioTrack) {

                user.audioTrack.play();

            }

        });

        window.currentCallClient.on('user-left', function() {

            if (typeof checkUnreadChats === 'function') { checkUnreadChats(); }

        });

        try {

            var tracks = call.call_type === 'video'

                ? await AgoraRTC.createMicrophoneAndCameraTracks()

                : [await AgoraRTC.createMicrophoneAudioTrack()];

            window.currentCallTracks = tracks;

            if (call.call_type === 'video' && tracks[1]) {

                $('#call-video-area').removeClass('d-none').html('<div id="remote-video-area" class="remote-video-screen"></div><div id="local-video-preview" class="local-video-preview"></div>');

                tracks[1].play('local-video-preview');

            }

            if (tracks[0] && call.call_type === 'audio') {

                // microphone track stays published only; no local playback to avoid echo.

            }

            await window.currentCallClient.join(rtc.app_id, rtc.channel, rtc.token, rtc.uid);

            await window.currentCallClient.publish(tracks);

            callRequest('{{ route('chat.call.connect', ['call' => '__CALL__']) }}'.replace('__CALL__', call.id), {}, function() {});
        } catch (error) {

            console.error('[agora call] join failed', error);

            AIZ.plugins.notify('danger', '{{ translate('Unable to access microphone or camera') }}');

            endActiveCall('failed');

        }

    }



    function stopCallSession(closeModal = true) {

        clearTimeout(window.currentCallTimer);

        clearInterval(window.currentCallDurationTimer);

        window.currentCallTimer = null;

        window.currentCallDurationTimer = null;

        window.currentCallStartedAt = null;

        if (window.currentCallTracks && window.currentCallTracks.length) {

            window.currentCallTracks.forEach(function(track) {

                if (track && track.close) { track.close(); }

            });

        }

        window.currentCallTracks = [];

        if (window.currentCallClient) {

            window.currentCallClient.leave().catch(function() {});

        }

        window.currentCallClient = null;

        setCallButtonsDisabled(false);

        if (closeModal !== false) {

            $('#call-screen-modal').modal('hide');

        }

    }



    function startIncomingCallCountdown(call) {

        clearTimeout(window.currentCallTimer);

        window.currentCallTimer = setTimeout(function() {

            if (window.currentCallState && window.currentCallState.id === call.id) {

                endActiveCall('missed');

            }

        }, 30000);

    }



    function startCallTimer() {

        window.currentCallStartedAt = new Date();

        clearInterval(window.currentCallDurationTimer);

        window.currentCallDurationTimer = setInterval(function() {

            if (!window.currentCallStartedAt) { return; }

            var diff = Math.max(0, Math.floor((Date.now() - window.currentCallStartedAt.getTime()) / 1000));

            var mm = String(Math.floor(diff / 60)).padStart(2, '0');

            var ss = String(diff % 60).padStart(2, '0');

            $('#call-screen-timer').text(mm + ':' + ss);

        }, 1000);

    }



    function appendCallTimelineEntry(call) {

        if (!call || !call.id) { return; }

        var icon = call.call_type === 'video' ? 'la-video' : 'la-phone';

        var label = call.status === 'missed' ? '{{ translate('Missed call') }}' : (call.status === 'rejected' ? '{{ translate('Declined call') }}' : (call.status === 'cancelled' ? '{{ translate('Cancelled call') }}' : (call.status === 'busy' ? '{{ translate('Busy call') }}' : (call.call_type === 'video' ? '{{ translate('Video call') }}' : '{{ translate('Audio call') }}'))));

        var duration = call.duration_seconds ? String(Math.floor(call.duration_seconds / 60)).padStart(2, '0') + ':' + String(call.duration_seconds % 60).padStart(2, '0') : '00:00';

        if ($('#call-log-' + call.id).length) { return; }

        var html = '<div id="call-log-' + call.id + '" class="chat-coversation call-log-item"><div class="media">';

        html += '<span class="avatar avatar-xs flex-shrink-0"><span class="call-log-icon"><i class="las ' + icon + '"></i></span></span>';

        html += '<div class="media-body"><div class="text call-log-card"><div class="d-flex justify-content-between align-items-center flex-wrap"><span><i class="las ' + icon + ' mr-1"></i>' + label + '</span><span class="badge badge-light">' + String(call.status || '').toUpperCase() + '</span></div><div class="small text-muted mt-1">' + duration + '</div></div><span class="time">' + (call.ended_at || call.started_at || call.created_at || '') + '</span></div>';

        html += '</div></div>';

        $('#chat-messages').append(html);

    }



    window.handleIncomingCallSignal = function(event) {

        var call = event && event.call ? event.call : event;

        if (!call || !call.id) { return; }

        showIncomingCallModal(call);

    };



    window.handleCallSignal = function(status, event) {

        var call = event && event.call ? event.call : event;

        if (!call || !call.id) { return; }

        if (status === 'accepted') {

            $('#call-action-modal').modal('hide');

            window.currentCallState = call;
            window.currentCallId = call.id;

            setCallButtonsDisabled(true);

            openActiveCallScreen(call);

            return;

        }

        if (['rejected', 'cancelled', 'busy', 'ended', 'missed'].indexOf(status) !== -1) {

            $('#call-action-modal').modal('hide');

            stopCallSession();

            appendCallTimelineEntry(call);

        }

    };

</script>



<style>
    #call-action-modal .d-flex.flex-wrap {
        gap: 16px !important;
    }
    #call-action-modal .btn-lg {
        min-width: 140px;
    }
    #call-controls .btn,
    .call-controls .btn {
        margin: 0 4px;
    }
</style>
<script type="text/javascript">
(function (window, $) {
    if (window.__hamqadamChatCallUiPatched) { return; }
    window.__hamqadamChatCallUiPatched = true;

    window.currentCallPeer = window.currentCallPeer || null;
    window.currentCallAudioTrack = window.currentCallAudioTrack || null;
    window.currentCallVideoTrack = window.currentCallVideoTrack || null;
    window.currentCallMicMuted = window.currentCallMicMuted || false;
    window.currentCallCameraMuted = window.currentCallCameraMuted || false;
    window.currentCallToneState = window.currentCallToneState || null;

    function avatarFallback() {
        return '{{ static_asset('assets/img/avatar-place.png') }}';
    }

    function getCallCounterpart(call) {
        var userId = parseInt(window.currentUserId || {{ Auth::id() }}, 10);
        if (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) { return call.peer; }
        var caller = call && call.caller ? call.caller : null;
        var receiver = call && call.receiver ? call.receiver : null;
        var callerId = caller && (caller.id || caller.user_id || caller.member_id) ? parseInt(caller.id || caller.user_id || caller.member_id, 10) : null;
        var receiverId = receiver && (receiver.id || receiver.user_id || receiver.member_id) ? parseInt(receiver.id || receiver.user_id || receiver.member_id, 10) : null;

        if (callerId && userId && callerId === userId) {
            return receiver || { name: call.receiver_name || call.to_name || '', photo: call.receiver_photo || call.to_photo || '' };
        }
        if (receiverId && userId && receiverId === userId) {
            return caller || { name: call.caller_name || call.from_name || '', photo: call.caller_photo || call.from_photo || '' };
        }

        return receiver || caller || { name: call.receiver_name || call.caller_name || '', photo: call.receiver_photo || call.caller_photo || '' };
    }

    function stopCallTone() {
        if (window.currentCallToneState && window.currentCallToneState.interval) {
            clearInterval(window.currentCallToneState.interval);
        }
        if (window.currentCallToneState && window.currentCallToneState.timers) {
            window.currentCallToneState.timers.forEach(function (timeoutId) { clearTimeout(timeoutId); });
        }
        if (window.currentCallToneState && window.currentCallToneState.context && window.currentCallToneState.context.close) {
            try { window.currentCallToneState.context.close(); } catch (e) {}
        }
        window.currentCallToneState = null;
    }

    window.playCallTone = function (kind) {
        stopCallTone();
        var AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) { return; }
        try {
            var context = new AudioContextClass();
            if (context.state === 'suspended' && context.resume) {
                context.resume().catch(function () {});
            }
            var tones = kind === 'incoming' ? [784, 988, 784] : [523, 659, 523, 659];
            var state = { context: context, timers: [], interval: null };
            function playSequence() {
                var gain = context.createGain();
                gain.gain.value = 0.03;
                gain.connect(context.destination);
                tones.forEach(function (frequency, index) {
                    var timeoutId = setTimeout(function () {
                        var osc = context.createOscillator();
                        osc.type = 'sine';
                        osc.frequency.value = frequency;
                        osc.connect(gain);
                        osc.start();
                        setTimeout(function () {
                            try { osc.stop(); } catch (e) {}
                            try { osc.disconnect(); } catch (e) {}
                        }, 190);
                    }, index * 230);
                    state.timers.push(timeoutId);
                });
            }
            playSequence();
            state.interval = setInterval(function () {
                playSequence();
            }, kind === 'incoming' ? 3200 : 3600);
            window.currentCallToneState = state;
        } catch (e) {
            console.warn('[' + (window.__hamqadamGlobalCallUiPatched ? 'global' : 'chat') + ' call] tone play failed', e);
        }
    };

    function updateMicIcon() {
        $('#call-toggle-mic i').attr('class', window.currentCallMicMuted ? 'las la-microphone-slash' : 'las la-microphone');
    }

    function updateCameraIcon() {
        $('#call-toggle-camera i').attr('class', window.currentCallCameraMuted ? 'las la-video-slash' : 'las la-video');
    }

    function callRequest(url, data, onSuccess) {
        return $.ajax({
            url: url,
            method: 'POST',
            data: Object.assign({_token: '{{ csrf_token() }}'}, data || {}),
            success: onSuccess,
            error: function(xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ translate('Calling service unavailable.') }}';
                AIZ.plugins.notify('danger', message);
            }
        });
    }

    window.startCall = function (type) {
        if (!$('#chat_thread_id').length) { return; }
        callRequest('{{ route('chat.call.start') }}', {
            chat_thread_id: $('#chat_thread_id').val(),
            call_type: type
        }, function(response) {
            if (!response.success) {
                AIZ.plugins.notify('danger', response.message || '{{ translate('Calling service unavailable.') }}');
                return;
            }
            window.currentCallState = response.data.call;
            window.currentCallState.rtc = response.data.rtc;
            window.showOutgoingCallModal(window.currentCallState);
            if (typeof setCallButtonsDisabled === 'function') { setCallButtonsDisabled(true); }
            if (typeof startIncomingCallCountdown === 'function') { startIncomingCallCountdown(window.currentCallState); }
            window.joinAgoraCall(window.currentCallState, response.data.rtc, true);
        });
    };

    window.showOutgoingCallModal = function (call) {
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#call-action-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        window.currentCallState = call;
        $('#call-shell-avatar').attr('src', peer.photo ? peer.photo : avatarFallback());
        $('#call-shell-name').text(peer.name ? peer.name : '{{ translate('Calling') }}');
        $('#call-shell-status').text('{{ translate('Calling...') }}');
        $('#call-shell-meta').html('<span class="badge badge-light">' + (call.call_type || 'audio') + '</span>');
        $('#call-shell-accept').addClass('d-none').removeData('call-id');
        $('#call-shell-decline').addClass('d-none').removeData('call-id');
        $('#call-shell-cancel').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call && call.id ? call.id : null);
        stopCallTone();
        window.playCallTone('outgoing');
        $('#call-action-modal').modal('show');
    };

    window.showIncomingCallModal = function (call) {
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#call-action-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        window.currentCallState = call;
        $('#call-shell-avatar').attr('src', peer.photo ? peer.photo : avatarFallback());
        $('#call-shell-name').text(peer.name ? peer.name : '{{ translate('Incoming Call') }}');
        $('#call-shell-status').text(call.call_type === 'video' ? '{{ translate('Incoming Video Call') }}' : '{{ translate('Incoming Audio Call') }}');
        $('#call-shell-meta').html('<div class="call-ringing-pill">{{ translate('Ringing') }}</div>');
        $('#call-shell-accept').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call.id);
        $('#call-shell-decline').removeClass('d-none').attr('data-call-id', call && call.id ? call.id : '').data('call-id', call.id);
        $('#call-shell-cancel').addClass('d-none');
        stopCallTone();
        window.playCallTone('incoming');
        $('#call-action-modal').modal('show');
        if (typeof setCallButtonsDisabled === 'function') { setCallButtonsDisabled(true); }
        if (typeof startIncomingCallCountdown === 'function') { startIncomingCallCountdown(call); }
    };

    window.openActiveCallScreen = function (call) {
        stopCallTone();
        window.currentCallState = call;
        window.currentCallId = call && call.id ? call.id : null;
        $('#call-screen-modal').attr('data-call-id', window.currentCallId);
        var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : (getCallCounterpart(call) || {});
        window.currentCallPeer = peer;
        window.currentCallState = call;
        $('#call-screen-name').text(peer.name ? peer.name : '{{ translate('Call') }}');
        $('#call-screen-status').text('{{ translate('Connected') }}');
        $('#call-screen-timer').text('00:00');
        if (call.call_type === 'video') {
            $('#call-video-area').removeClass('d-none').html('<div id="remote-video-area" class="remote-video-screen"></div><div id="local-video-preview" class="local-video-preview"></div>');
            $('#call-audio-area').addClass('d-none');
            $('#call-toggle-camera, #call-switch-camera').removeClass('d-none');
        } else {
            $('#call-video-area').addClass('d-none').empty();
            $('#call-audio-area').removeClass('d-none').html('<div class="call-audio-avatar mb-3"><img src="' + (peer.photo ? peer.photo : avatarFallback()) + '" alt="avatar"></div><h3 class="mb-0">' + (peer.name ? peer.name : '') + '</h3><p class="mb-0 text-white-50">{{ translate('Connected') }}</p>');
            $('#call-toggle-camera, #call-switch-camera').addClass('d-none');
        }
        $('#call-screen-modal').modal('show');
        if (typeof startCallTimer === 'function') {
            startCallTimer();
        }
    };

    window.joinAgoraCall = async function (call, rtc, isCaller) {
        if (typeof AgoraRTC === 'undefined' || !rtc || !rtc.token) {
            AIZ.plugins.notify('danger', '{{ translate('Calling service unavailable.') }}');
            return;
        }
        if (window.currentCallClient || (window.currentCallTracks && window.currentCallTracks.length)) {
            window.stopCallSession(false);
        }
        window.currentCallClient = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });
        window.currentCallClient.on('user-published', async function(user, mediaType) {
            await window.currentCallClient.subscribe(user, mediaType);
            if (mediaType === 'video') {
                $('#call-video-area').removeClass('d-none').html('<div id="remote-video-area" class="remote-video-screen"></div><div id="local-video-preview" class="local-video-preview"></div>');
                if (user.videoTrack) { user.videoTrack.play('remote-video-area'); }
            }
            if (mediaType === 'audio' && user.audioTrack) {
                user.audioTrack.play();
            }
        });
        window.currentCallClient.on('user-left', function() {
            if (typeof checkUnreadChats === 'function') { checkUnreadChats(); }
        });
        try {
            var tracks = call.call_type === 'video'
                ? await AgoraRTC.createMicrophoneAndCameraTracks()
                : [await AgoraRTC.createMicrophoneAudioTrack()];
            window.currentCallTracks = tracks;
            window.currentCallAudioTrack = tracks[0] || null;
            window.currentCallVideoTrack = call.call_type === 'video' ? (tracks[1] || null) : null;
            window.currentCallMicMuted = false;
            window.currentCallCameraMuted = false;
            updateMicIcon();
            updateCameraIcon();
            if (call.call_type === 'video' && tracks[1]) {
                $('#call-video-area').removeClass('d-none').html('<div id="remote-video-area" class="remote-video-screen"></div><div id="local-video-preview" class="local-video-preview"></div>');
                tracks[1].play('local-video-preview');
            }
            await window.currentCallClient.join(rtc.app_id, rtc.channel, rtc.token, rtc.uid);
            await window.currentCallClient.publish(tracks);
            $.ajax({
                url: '{{ route('chat.call.connect', ['call' => '__CALL__']) }}'.replace('__CALL__', call.id),
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' }
            });
            window.openActiveCallScreen(call);
            if (typeof startCallTimer === 'function') {
                startCallTimer();
            }
        } catch (error) {
            console.error('[chat call] join failed', error);
            AIZ.plugins.notify('danger', '{{ translate('Unable to access microphone or camera') }}');
            window.endActiveCall('failed');
        }
    };

    window.stopCallSession = function (closeModal) {
        stopCallTone();
        clearTimeout(window.currentCallTimer);
        clearInterval(window.currentCallDurationTimer);
        window.currentCallTimer = null;
        window.currentCallDurationTimer = null;
        window.currentCallStartedAt = null;
        if (window.currentCallTracks && window.currentCallTracks.length) {
            window.currentCallTracks.forEach(function(track) {
                if (track && track.close) { track.close(); }
            });
        }
        window.currentCallTracks = [];
        window.currentCallAudioTrack = null;
        window.currentCallVideoTrack = null;
        window.currentCallMicMuted = false;
        window.currentCallCameraMuted = false;
        updateMicIcon();
        updateCameraIcon();
        if (window.currentCallClient) {
            window.currentCallClient.leave().catch(function() {});
        }
        window.currentCallClient = null;
        if (typeof setCallButtonsDisabled === 'function') { setCallButtonsDisabled(false); }
        if (closeModal !== false) {
            $('#call-screen-modal').modal('hide');
            $('#call-action-modal').modal('hide');
        }
    };

    window.toggleCallMic = function () {
        var track = window.currentCallAudioTrack || (window.currentCallTracks || []).find(function(item) { return item && item.trackMediaType === 'audio'; });
        if (!track || !track.setEnabled) { return; }
        window.currentCallMicMuted = !window.currentCallMicMuted;
        track.setEnabled(!window.currentCallMicMuted);
        updateMicIcon();
    };

    window.toggleCallCamera = function () {
        var track = window.currentCallVideoTrack || (window.currentCallTracks || []).find(function(item) { return item && item.trackMediaType === 'video'; });
        if (!track || !track.setEnabled) { return; }
        window.currentCallCameraMuted = !window.currentCallCameraMuted;
        track.setEnabled(!window.currentCallCameraMuted);
        updateCameraIcon();
    };

    window.switchCallCamera = function () {
        var track = window.currentCallVideoTrack || (window.currentCallTracks || []).find(function(item) { return item && item.trackMediaType === 'video'; });
        if (!track || !navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) { return; }
        navigator.mediaDevices.enumerateDevices().then(function(devices) {
            var cameras = devices.filter(function(device) { return device.kind === 'videoinput'; });
            if (cameras.length > 1 && track.setDevice) {
                track.setDevice(cameras[1].deviceId);
            }
        });
    };
})(window, window.jQuery);
</script>
    <style>
        #call-action-modal .modal-dialog {
            max-width: 420px;
            width: calc(100vw - 32px);
        }
        #call-action-modal .modal-content {
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(255, 47, 132, 0.22);
        }
        #call-action-modal .call-shell {
            padding: 28px 24px !important;
        }
        #call-action-modal .btn-lg {
            min-width: 126px;
            border-radius: 16px;
        }
        #call-action-modal .d-flex.flex-wrap {
            gap: 14px !important;
        }
        #call-action-modal .call-shell-avatar {
            width: 104px;
            height: 104px;
        }
        #call-screen-modal .modal-dialog {
            max-width: 920px;
            width: calc(100vw - 20px);
        }
    </style>
    <script type="text/javascript">
    (function (window, $) {
        if (!window.__hamqadamChatCallUiTweaked) {
            window.__hamqadamChatCallUiTweaked = true;
            window.currentCallPeer = window.currentCallPeer || null;
            var originalShowOutgoing = window.showOutgoingCallModal;
            var originalShowIncoming = window.showIncomingCallModal;
            var originalOpenScreen = window.openActiveCallScreen;
            window.showOutgoingCallModal = function (call) {
                var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : ((typeof getCallCounterpart === 'function' ? getCallCounterpart(call) : null) || {});
                window.currentCallPeer = peer;
                if (typeof originalShowOutgoing === 'function') { originalShowOutgoing(call); }
            };
            window.showIncomingCallModal = function (call) {
                var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : ((typeof getCallCounterpart === 'function' ? getCallCounterpart(call) : null) || {});
                window.currentCallPeer = peer;
                if (typeof originalShowIncoming === 'function') { originalShowIncoming(call); }
            };
            window.openActiveCallScreen = function (call) {
                var peer = (call && call.peer && (call.peer.name || call.peer.photo || call.peer.id)) ? call.peer : ((typeof getCallCounterpart === 'function' ? getCallCounterpart(call) : null) || {});
                window.currentCallPeer = peer;
                if (typeof originalOpenScreen === 'function') {
                    originalOpenScreen(call);
                    return;
                }
            };
        }
    })(window, window.jQuery);
    </script>
@endsection