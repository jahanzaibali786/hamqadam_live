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
@endsection
