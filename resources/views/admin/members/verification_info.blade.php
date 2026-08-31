@extends('admin.layouts.app')
@section('content')
@php
    $requestStatus = $verificationRequest?->status instanceof \BackedEnum ? $verificationRequest->status->value : $verificationRequest?->status;
    $faceMatchStatus = $verificationRequest?->face_match_status instanceof \BackedEnum ? $verificationRequest->face_match_status->value : $verificationRequest?->face_match_status;
    $manualReview = optional($user->member)->verification_status === 'manual_review' || optional($user->member)->ai_verification_status === 'manual_review';
@endphp

<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Member Verification') }}</h5>
                @if($manualReview)
                    <span class="badge badge-inline badge-warning">{{ translate('Manual Review') }}</span>
                @elseif($user->approved == 1)
                    <span class="badge badge-inline badge-success">{{ translate('Approved') }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 class="mb-3">{{ translate('User Info') }}</h6>
                        <div class="text-center mb-4">
                            <img src="{{ $user->photo ? uploaded_asset($user->photo) : static_asset('assets/img/avatar-place.png') }}" class="img-fluid rounded border" style="max-height:220px;object-fit:cover;" alt="{{ $user->first_name }}">
                        </div>
                        <p class="text-muted mb-2"><strong>{{ translate('Code') }} :</strong> <span class="ml-2">{{ $user->code }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Name') }} :</strong> <span class="ml-2">{{ $user->first_name.' '.$user->last_name }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Email') }} :</strong> <span class="ml-2">{{ $user->email }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Phone') }} :</strong> <span class="ml-2">{{ $user->phone }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Approval') }} :</strong> <span class="ml-2">{{ $user->approved == 1 ? translate('Approved') : translate('Pending') }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Profile Verification') }} :</strong> <span class="ml-2">{{ ucfirst(str_replace('_', ' ', optional($user->member)->verification_status ?? 'not_set')) }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('AI Verification') }} :</strong> <span class="ml-2">{{ ucfirst(str_replace('_', ' ', optional($user->member)->ai_verification_status ?? 'not_started')) }}</span></p>
                        <p class="text-muted mb-2"><strong>{{ translate('Photo Approval') }} :</strong> <span class="ml-2">{{ $user->photo_approved == 1 ? translate('Approved') : translate('Pending') }}</span></p>
                    </div>
                    <div class="col-md-8">
                        <h6 class="mb-3">{{ translate('Verification Request') }}</h6>
                        @if($verificationRequest)
                            <div class="table-responsive mb-4">
                                <table class="table table-striped table-bordered">
                                    <tbody>
                                        <tr><th>{{ translate('Status') }}</th><td>{{ ucfirst(str_replace('_', ' ', $requestStatus ?? 'draft')) }}</td></tr>
                                        <tr><th>{{ translate('CNIC Number') }}</th><td>{{ $verificationRequest->cnic_number ?? '-' }}</td></tr>
                                        <tr><th>{{ translate('Face Match Status') }}</th><td>{{ ucfirst(str_replace('_', ' ', $faceMatchStatus ?? 'pending')) }}</td></tr>
                                        <tr><th>{{ translate('Face Match Score') }}</th><td>{{ $verificationRequest->face_match_score !== null ? number_format((float) $verificationRequest->face_match_score, 2) : '-' }}</td></tr>
                                        <tr><th>{{ translate('Submitted At') }}</th><td>{{ optional($verificationRequest->submitted_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><th>{{ translate('Reviewed At') }}</th><td>{{ optional($verificationRequest->reviewed_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                                        <tr><th>{{ translate('Rejected Reason') }}</th><td>{{ $verificationRequest->rejection_reason ?? '-' }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">{{ translate('No structured verification request was found. Showing the registration verification form payload below.') }}</div>
                        @endif

                        @if ($user->verification_info != null)
                            <h6 class="mb-3">{{ translate('Submitted Verification Form') }}</h6>
                            <table class="table table-striped table-bordered" cellspacing="0" width="100%">
                                <tbody>
                                    @foreach (json_decode($user->verification_info) as $key => $info)
                                        <tr>
                                            <th class="text-muted">{{ $info->label }}</th>
                                            @if ($info->type == 'text' || $info->type == 'select' || $info->type == 'radio')
                                                <td>{{ $info->value }}</td>
                                            @elseif ($info->type == 'multi_select')
                                                <td>{{ $info->value ? implode(', ', json_decode($info->value, true) ?? []) : '' }}</td>
                                            @elseif ($info->type == 'file')
                                                <td>
                                                    <a href="{{ static_asset($info->value) }}" target="_blank" class="btn btn-sm btn-info">{{ translate('Open File') }}</a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <h6 class="mb-3 mt-4">{{ translate('Uploaded Documents') }}</h6>
                        @if($verificationRequest && $verificationRequest->documents && $verificationRequest->documents->count())
                            <div class="row">
                                @foreach($verificationRequest->documents as $document)
                                    @php
                                        $documentType = $document->type instanceof \BackedEnum ? $document->type->value : $document->type;
                                        $documentUrl = $document->upload_id ? uploaded_asset($document->upload_id) : ($document->file_path ? static_asset(ltrim($document->file_path, '/')) : null);
                                    @endphp
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $documentType ?? 'document')) }}</strong>
                                                @if($documentUrl)
                                                    <a href="{{ $documentUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">{{ translate('View') }}</a>
                                                @endif
                                            </div>
                                            @if($documentUrl)
                                                <img src="{{ $documentUrl }}" alt="{{ $documentType }}" class="img-fluid rounded border w-100" style="max-height:240px;object-fit:contain;">
                                            @else
                                                <div class="text-muted">{{ translate('No file path available.') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted">{{ translate('No uploaded verification documents were found.') }}</div>
                        @endif

                        @if ($user->approved != 1 && ($user->verification_info != null || $verificationRequest))
                            <div class="text-right mt-4">
                                <a href="javascript:void(0);" onclick="verify_member('{{ route('member.reject_verification', $user->id) }}','reject')" class="btn btn-sm btn-danger d-innline-block">{{ translate('Reject') }}</a>
                                <a href="javascript:void(0);" onclick="verify_member('{{ route('member.approve_verification', $user->id) }}','approve')" class="btn btn-sm btn-success d-innline-block">{{ translate('Accept') }}</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('modal')
    <div class="modal fade member-verification-modal" id="modal-basic">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Member Verification') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-1" id="verify_member_text"></p>
                    <button type="button" class="btn btn-sm btn-light mt-2" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="submit" class="btn btn-sm btn-primary mt-2" id="confirm-link">{{ translate('Confirm') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
    function verify_member(url, status){
        var confirmation_text =  status == 'approve' ? 
                                "{{ translate('Are you sure to approve this verification?') }}" : 
                                "{{ translate('Are you sure to reject this verification?') }}";

        $('.member-verification-modal').modal('show');
        $('#verify_member_text').html(confirmation_text);
        $('#confirm-link').attr('href', url);
    }
</script>
@endsection
