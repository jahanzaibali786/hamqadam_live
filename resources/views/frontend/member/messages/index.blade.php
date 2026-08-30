@extends('frontend.layouts.member_panel')
@section('panel_content')
<style>
    .dropdown-toggle::after{
        display:none !important;
    }
</style>
    <div class="aiz-chat">
        <div class="row no-gutters">
            <div class="col-lg-4">
                <div class="chat-user-list-wrap z-1035">
                    <div class="overlay dark c-pointer" data-toggle="class-toggle" data-target=".chat-user-list-wrap" data-same=".aiz-all-chat-toggler"></div>
                    <div class="chat-user-list-header d-flex justify-content-between align-items-center bg-white border-bottom border-right h6 mb-0">
                        <span class="p-2 m-1">{{translate('Members')}}</span>
                        <button class="btn btn-icon d-lg-none" data-toggle="class-toggle" data-target=".chat-user-list-wrap"><i class="las la-times"></i></button>
                    </div>
                    <div class="chat-user-list border-right py-3 c-scrollbar-light">
                        @forelse ($chat_threads as $key => $single_chat_thread)
                            @php
                                $num_of_message = $single_chat_thread->chats->where('seen', 0)->count();
                                $current_user = Auth::user()->id;
                            @endphp
                            @if ($single_chat_thread->receiver != null && $single_chat_thread->sender != null)
                              <a href="javascript:void(0)" class="chat-user-item p-3 d-block text-inherit" data-thread-id="{{ $single_chat_thread->id }}" data-url="{{ route('chat_view', $single_chat_thread->id) }}" data-refresh="{{ route('chat_refresh', $single_chat_thread->id) }}" onclick="loadChats(this)">
                                  @if($current_user == $single_chat_thread->sender->id)
                                    @php $user_to_show = 'receiver';  @endphp
                                  @else
                                    @php $user_to_show = 'sender';  @endphp
                                  @endif
                                  <div class="media">
                                      <span class="avatar avatar-sm mr-3 flex-shrink-0">
                                          @if ($single_chat_thread->$user_to_show->photo != null)
                                          <img src="{{ uploaded_asset($single_chat_thread->$user_to_show->photo) }}">
                                          @else
                                          <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                                          @endif

                                          @if(Cache::has('user-is-online-' . $single_chat_thread->$user_to_show->id))
                                              <span class="badge badge-dot badge-circle badge-success badge-status badge-md"></span>
                                          @else
                                              <span class="badge badge-dot badge-circle badge-secondary badge-status badge-md"></span>
                                          @endif
                                      </span>
                                      <div class="media-body minw-0">
                                          <h6 class="mt-0 mb-1 fs-14 text-truncate">{{ $single_chat_thread->$user_to_show->first_name.' '.$single_chat_thread->$user_to_show->last_name }}</h6>
                                            @php
                                                $visible_last_chat = $single_chat_thread->chats->filter(function ($chat) use ($current_user, $single_chat_thread) {
                                                    if ((int) $current_user === (int) $single_chat_thread->sender->id) {
                                                        return is_null($chat->deleted_by_sender_at);
                                                    }

                                                    return is_null($chat->deleted_by_receiver_at);
                                                })->last();
                                            @endphp
                                            @if ($visible_last_chat != null)
                                                @if ($visible_last_chat->message != null)
                                                    <div class="fs-12 text-truncate opacity-60">{{ $visible_last_chat->message }}</div>
                                                @else
                                                    <div class="fs-12 text-truncate opacity-60">{{ translate('Attachments')}}</div>
                                                @endif
                                            @endif
                                       </div>
                                      <div class="ml-2 text-right">
                                          @if ($single_chat_thread->chats->last() != null)
                                              <div class="opacity-60 fs-10 mb-1">{{ Carbon\Carbon::parse($single_chat_thread->chats->last()->created_at)->diffForHumans() }}</div>
                                          @endif
                                            @php $unseen_count = count($single_chat_thread->chats->where('sender_user_id', '!=', Auth::user()->id)->where('seen', 0)); @endphp
                                            @if($unseen_count > 0)
                                            <span class="badge badge-primary badge-circle flex-shrink-0 ml-4">{{ $unseen_count }}</span>
                                            @endif
                                      </div>
                                  </div>
                              </a>
                            @endif
                        @empty
                            <div class=" text-center">
                                <i class="las la-frown la-4x mb-4 opacity-40"></i>
                                <h4>{{ translate('Nothing Found')}}</h4>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-8" id="single_chat">
                <div class="chat-box-wrap h-100">
                    <div class="attached-top bg-white border-bottom chat-header d-flex justify-content-between align-items-center p-3 shadow-sm">
                        <div class="media">
                            <h6 class="mb-0">{{ translate('Chats')}}</h6>
                        </div>
                        <button class="aiz-mobile-toggler d-lg-none aiz-all-chat-toggler mr-2" data-toggle="class-toggle" data-target=".chat-user-list-wrap">
                            <span></span>
                        </button>
                    </div>
                    <div class="px-3 py-5 text-center">
                        <i class="las la-user la-6x text-primary mb-4"></i>
                        <h5>{{ translate('Select a Member to view chats') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.package_update_alert_modal')

    <div class="modal fade" id="chat-report-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="chat-report-form">
                    @csrf
                    <input type="hidden" name="chat_thread_id" id="chat-report-thread-id">
                    <div class="modal-header">
                        <h5 class="modal-title h6">{{ translate('Report Chat') }}</h5>
                        <button type="button" class="close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 text-muted fs-12" id="chat-report-target"></div>
                        <div class="form-group">
                            <label>{{ translate('Reason') }}</label>
                            <textarea class="form-control" name="reason" id="chat-report-reason" rows="4" required placeholder="{{ translate('Tell us why you are reporting this chat') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Submit Report') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function loadChats(el){
            $(".selected-chat").each(function() {
                $(this).removeClass("bg-soft-primary");
                $(this).removeClass("selected-chat");
            });
            $(el).addClass("selected-chat");
            $(el).addClass("bg-soft-primary");
            window.activeChatThreadId = parseInt($(el).data("thread-id")) || null;
            $.get($(el).data("url"), {}, function(data){
                $("#single_chat").html(data);
                AIZ.extra.scrollToBottom();
                initializeLoadMore();
                bindChatThreadRealtime();
                window.activeChatThreadId = $("#chat_thread_id").length ? parseInt($("#chat_thread_id").val()) : window.activeChatThreadId;
                refreshOpenedThread($(el));
                if (typeof checkUnreadChats === "function") {
                    checkUnreadChats();
                }
                $("#send-mesaage").off("submit").on("submit", function(e){
                    e.preventDefault();
                    send_reply();
                });
            });
        }

        function refreshOpenedThread($el, callback) {
            var refreshUrl = $el.data('refresh');
            if (!refreshUrl) {
                if (typeof callback === 'function') {
                    callback(null);
                }
                return;
            }

            $.get(refreshUrl, {}, function(response) {
                if (typeof response === 'object' && response.count !== undefined) {
                    var badge = $el.find('.badge.badge-primary.badge-circle');
                    if (parseInt(response.count) > 0) {
                        badge.text(response.count).show();
                    } else {
                        badge.remove();
                    }
                }

                if (typeof callback === 'function') {
                    callback(response);
                }
            });
        }

        window.markActiveChatThreadSeen = function (callback) {
            var currentRow = $('.chat-user-item[data-thread-id="' + $('#chat_thread_id').val() + '"]').first();
            if (!currentRow.length) {
                if (typeof callback === 'function') {
                    callback();
                }
                return;
            }

            refreshOpenedThread(currentRow, callback);
        };

        function bindChatThreadRealtime() {
            if (typeof window.Echo === 'undefined' || !$('#chat_thread_id').length) {
                return;
            }

            const threadId = parseInt($('#chat_thread_id').val(), 10);
            if (!threadId) {
                return;
            }

            if (window.activeRealtimeThreadChannel && window.activeRealtimeThreadChannel !== `chat-thread.${threadId}`) {
                window.Echo.leave(window.activeRealtimeThreadChannel);
            }

            window.activeRealtimeThreadChannel = `chat-thread.${threadId}`;
            window.Echo.leave(window.activeRealtimeThreadChannel);
            window.Echo.private(window.activeRealtimeThreadChannel)
                .listen('.message-sent', function (event) {
                    var payload = normalizeChatEvent(event);
                    var payloadThreadId = payload && payload.thread_id ? parseInt(payload.thread_id, 10) : null;

                    if (payloadThreadId !== threadId) {
                        return;
                    }

                    if (payload.sender && parseInt(payload.sender.id, 10) === {{ Auth::id() }}) {
                        return;
                    }

                    if (payload.message || (payload.attachments && payload.attachments.length)) {
                        appendIncomingChatMessage(payload);
                        updateThreadSidebarPreview(payload);
                        refreshOpenedThread($(".chat-user-item[data-thread-id=\"" + threadId + "\"]").first(), function () {
                            if (typeof checkUnreadChats === 'function') {
                                checkUnreadChats();
                            }
                        });
                    }
                })
                .listen('.message-read', function (event) {
                    var payload = normalizeChatEvent(event);
                    var payloadThreadId = payload && payload.thread_id ? parseInt(payload.thread_id, 10) : null;

                    if (payloadThreadId !== threadId) {
                        return;
                    }

                    if (typeof checkUnreadChats === 'function') {
                        checkUnreadChats();
                    }
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
            var avatar = event.sender && event.sender.photo ? event.sender.photo : "{{ static_asset('assets/img/avatar-place.png') }}";
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

        function updateThreadSidebarPreview(payload) {
            var threadId = payload.thread_id || (payload.reply_to && payload.reply_to.thread_id);
            if (!threadId) {
                return;
            }

            var row = $(".chat-user-item[data-thread-id=\"" + threadId + "\"]");
            if (!row.length) {
                return;
            }

            var preview = row.find(".media-body .fs-12.text-truncate.opacity-60").first();
            if (preview.length) {
                preview.text(payload.message || "{{ translate('Attachments') }}");
            }

            var timeBox = row.find(".ml-2.text-right .opacity-60.fs-10.mb-1").first();
            if (timeBox.length) {
                timeBox.text("{{ translate('Just now') }}");
            }

            var currentActiveThreadId = window.activeChatThreadId ? parseInt(window.activeChatThreadId, 10) : null;
            var normalizedThreadId = parseInt(threadId, 10);
            if (currentActiveThreadId && normalizedThreadId === currentActiveThreadId) {
                var activeBadge = row.find(".badge.badge-primary.badge-circle").first();
                if (activeBadge.length) {
                    activeBadge.remove();
                }
                return;
            }

            var badge = row.find(".badge.badge-primary.badge-circle").first();
            if (badge.length) {
                var current = parseInt(badge.text() || "0", 10);
                badge.text((isNaN(current) ? 0 : current) + 1).show();
            } else {
                row.find(".ml-2.text-right").append("<span class=\"badge badge-primary badge-circle flex-shrink-0 ml-4\">1</span>");
            }
        }

        window.handleIncomingChatSidebarEvent = function (payload) {
            updateThreadSidebarPreview(payload);
        };



        function chatActionRequest(route, payload, successMessage, afterSuccess) {
            payload = payload || {};
            payload._token = "{{ csrf_token() }}";

            return $.post(route, payload, function (response) {
                if (response && response.success === false) {
                    AIZ.plugins.notify("danger", response.message || "{{ translate("Something went wrong") }}");
                    return;
                }

                if (successMessage) {
                    AIZ.plugins.notify("success", successMessage);
                }

                if (typeof afterSuccess === "function") {
                    afterSuccess(response);
                }
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "{{ translate("Something went wrong") }}";
                AIZ.plugins.notify("danger", message);
            });
        }

        function blockChatThread() {
            var threadId = $("#chat_thread_id").val();
            if (!threadId) {
                return;
            }

            chatActionRequest("{{ route("chat.block") }}", { chat_thread_id: threadId }, "{{ translate("Chat blocked successfully") }}", function () {
                window.location.reload();
            });
        }

        function unblockChatThread() {
            var threadId = $("#chat_thread_id").val();
            if (!threadId) {
                return;
            }

            chatActionRequest("{{ route("chat.unblock") }}", { chat_thread_id: threadId }, "{{ translate("Chat unblocked successfully") }}", function () {
                window.location.reload();
            });
        }

        function updateThreadSidebarPreviewCleared(threadId) {
            var row = $(".chat-user-item[data-thread-id='" + threadId + "']").first();
            if (!row.length) {
                return;
            }

            var preview = row.find('.fs-12.text-truncate.opacity-60').first();
            if (preview.length) {
                preview.text("{{ translate('Chat cleared') }}");
            }

            var timeBox = row.find('.opacity-60.fs-10.mb-1').first();
            if (timeBox.length) {
                timeBox.text("{{ translate('Just now') }}");
            }

            row.find('.badge.badge-primary.badge-circle').remove();
        }

        function clearChatThread() {
            var threadId = $("#chat_thread_id").val();
            if (!threadId || !confirm("{{ translate("Clear this chat only for your side?") }}")) {
                return;
            }

            chatActionRequest("{{ route("chat.clear") }}", { chat_thread_id: threadId }, "{{ translate("Chat cleared successfully") }}", function () {
                $("#chat-messages").empty();
                updateThreadSidebarPreviewCleared(threadId);
                refreshOpenedThread($(".chat-user-item[data-thread-id='" + threadId + "']").first());
            });
        }

        function openChatReportModal() {
            var threadId = $("#chat_thread_id").val();
            if (!threadId) {
                return;
            }

            var targetName = $.trim($(".chat-info-wrap .fw-600").first().text() || $(".chat-header h6.mb-0").first().text());
            $("#chat-report-thread-id").val(threadId);
            $("#chat-report-target").text(targetName ? ("{{ translate('Report chat with') }} " + targetName) : "{{ translate('Report Chat') }}");
            $("#chat-report-reason").val('');
            $("#chat-report-modal").modal('show');
        }

        $(document).on('submit', '#chat-report-form', function (e) {
            e.preventDefault();
            var threadId = $("#chat-report-thread-id").val();
            var reason = $.trim($("#chat-report-reason").val());
            if (!threadId || !reason.length) {
                return;
            }

            chatActionRequest("{{ route("chat.report") }}", { chat_thread_id: threadId, reason: reason }, "{{ translate("Chat reported successfully") }}", function () {
                $("#chat-report-modal").modal('hide');
                window.location.reload();
            });
        });

        $(document).on("click", ".chat-delete-message", function (e) {
            e.preventDefault();
            var messageId = $(this).data("message-id");
            if (!messageId || !confirm("{{ translate("Delete this message only for your side?") }}")) {
                return;
            }

            var button = $(this);
            chatActionRequest("{{ route("chat.message.delete") }}", { message_id: messageId }, "{{ translate("Message deleted") }}", function () {
                button.closest(".chat-coversation").remove();
            });
        });

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (m) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
            });
        }

        function send_reply(){
            var chat_thread_id = $("#chat_thread_id").val();
            var message = $("#message").val();
            var attachment = $("#attachment").val();
            if(message.length > 0 || attachment.length > 0){
                $.post("{{ route("chat.reply") }}",{_token:"{{ csrf_token() }}", chat_thread_id:chat_thread_id, message:message, attachment:attachment}, function(data){
                    $("#message").val("");
                    $("#attachment").val("");
                    $("#chat-messages").append(data);
                    AIZ.extra.scrollToBottom();
                }).fail(function(xhr){
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "{{ translate("Something went wrong") }}";
                    AIZ.plugins.notify("danger", message);
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

        $(document).ready(function () {
            bindChatThreadRealtime();
        });

        function initializeLoadMore(){
            $('.load-more-btn').off('click').on('click', function(){
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
@endsection@endsection
