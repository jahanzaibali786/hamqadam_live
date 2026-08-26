
@if ($chat->message != null)
    <div class="chat-coversation right">
        <div class="media">
            <div class="media-body">
                <div class="text bg-soft-primary text-dark">{{ $chat->message }}</div>
                <div class="d-flex justify-content-end align-items-center mt-1">
                    <a href="javascript:void(0)" class="text-danger fs-12 chat-delete-message" data-message-id="{{ $chat->id }}" title="Delete message">
                        <i class="las la-trash"></i>
                    </a>
                    <span class="time ml-2">{{ Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}</span>
                </div>
            </div>
            <span class="avatar avatar-xs flex-shrink-0">
                @if ($chat->sender->photo != null)
                    <img src="{{ uploaded_asset($chat->sender->photo) }}">
                @else
                    <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                @endif
            </span>
        </div>
    </div>
@endif
@if ($chat->attachment != null)
    <div class="chat-coversation right">
        <div class="media">
            <div class="media-body">
                <div class="file-preview box sm">
                    @foreach (json_decode($chat->attachment) as $key => $attachment_id)
                        @php
                            $attachment = \App\Models\Upload::find($attachment_id);
                        @endphp
                        @if ($attachment != null)
                            @if ($attachment->type == 'image')
                                <div class="mb-2 file-preview-item" title="{{ $attachment->file_name }}">
                                    <a href="{{ route('download_attachment', $attachment->id) }}" target="_blank" class="d-block">
                                        <div class="thumb">
                                            <img src="{{ static_asset($attachment->file_name) }}" class="img-fit">
                                        </div>
                                        <div class="body">
                                            <h6 class="d-flex">
                                                <span class="text-truncate title">{{ $attachment->file_original_name }}</span>
                                                <span class="ext">.{{ $attachment->extension }}</span>
                                            </h6>
                                            <p>{{formatBytes($attachment->file_size)}}</p>
                                        </div>
                                    </a>
                                </div>
                            @else
                                <div class="mb-2 file-preview-item" title="{{ $attachment->file_name }}">
                                    <a href="{{ route('download_attachment', $attachment->id) }}" target="_blank" class="d-block">
                                        <div class="thumb">
                                            <i class="la la-file-text"></i>
                                        </div>
                                        <div class="body">
                                            <h6 class="d-flex">
                                                <span class="text-truncate title">{{ $attachment->file_original_name }}</span>
                                                <span class="ext">.{{ $attachment->extension }}</span>
                                            </h6>
                                            <p>{{formatBytes($attachment->file_size)}}</p>
                                        </div>
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="alert alert-secondary" role="alert">
                                {{ translate('No attachment') }}
                            </div>
                        @endif
                    @endforeach
                </div>
                
                <div class="d-flex justify-content-end align-items-center mt-1">
                    <a href="javascript:void(0)" class="text-danger fs-12 chat-delete-message" data-message-id="{{ $chat->id }}" title="Delete message">
                        <i class="las la-trash"></i>
                    </a>
                </div><span class="time">{{ Carbon\Carbon::parse($chat->created_at)->diffForHumans() }}</span>
            </div>
            <span class="avatar avatar-xs flex-shrink-0">
                @if ($chat->sender->photo != null)
                <img src="{{ uploaded_asset($chat->sender->photo) }}">
                @else
                <img src="{{ static_asset('assets/img/avatar-place.png') }}">
                @endif
            </span>
        </div>
    </div>
@endif
