@php
    $profile = $member->member;
    $presentAddress = \App\Models\Address::where('type', 'present')->where('user_id', $member->id)->first();
    $verification = \App\Models\ProfileVerificationRequest::with('documents')->where('user_id', $member->id)->latest()->first();
    $snapshot = [];
    if ($profile && \Illuminate\Support\Facades\Schema::hasColumn('members', 'registration_steps') && $profile->registration_steps) {
        $snapshot = json_decode($profile->registration_steps, true) ?: [];
    }
    $yesNo = fn($value) => is_null($value) ? translate('Depends on Mutual Understanding') : ($value ? translate('Yes') : translate('No'));
    $row = function ($label, $value) {
        if (is_array($value)) {
            $value = implode(', ', array_filter($value));
        }
        return ['label' => $label, 'value' => $value];
    };
    $items = [
        $row(translate('Marriage Timeline'), $profile->marriage_timeline ?? null),
        $row(translate('Expected Spouse Work'), isset($profile->expects_spouse_to_work) ? $yesNo($profile->expects_spouse_to_work) : null),
        $row(translate('Work After Marriage'), isset($profile->willing_to_work_after_marriage) ? $yesNo($profile->willing_to_work_after_marriage) : null),
        $row(translate('Area'), $presentAddress->area ?? null),
        $row(translate('Education Level'), $profile->education_level ?? null),
        $row(translate('Annual Income'), $profile->annual_income ?? null),
        $row(translate('Father Occupation'), $profile->father_occupation ?? null),
        $row(translate('Mother Occupation'), $profile->mother_occupation ?? null),
        $row(translate('Brothers'), $profile->siblings_brothers ?? null),
        $row(translate('Sisters'), $profile->siblings_sisters ?? null),
        $row(translate('Family Location'), $profile->family_location ?? null),
        $row(translate('Family Financial Status'), $profile->family_values ?? null),
        $row(translate('Hobbies / Interests'), $profile->hobbies ?? null),
        $row(translate('CNIC Number'), $verification->cnic_number ?? ($snapshot['cnic_number'] ?? null)),
        $row(translate('Verification Status'), $profile->verification_status ?? null),
    ];
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Registration Details') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($items as $item)
                @if($item['value'] !== null && $item['value'] !== '')
                    <div class="col-md-6 mb-3">
                        <div class="small text-muted">{{ $item['label'] }}</div>
                        <div class="fw-600">{{ $item['value'] }}</div>
                    </div>
                @endif
            @endforeach
        </div>

        @if($verification && $verification->documents->count())
            <hr>
            <div class="row">
                @foreach($verification->documents as $document)
                    <div class="col-md-4 mb-3">
                        <div class="small text-muted">{{ ucwords(str_replace('_', ' ', $document->type->value ?? $document->type)) }}</div>
                        @if($document->upload_id)
                            <a href="{{ uploaded_asset($document->upload_id) }}" target="_blank">
                                <img src="{{ uploaded_asset($document->upload_id) }}" class="img-fluid rounded border" alt="{{ translate('Verification Document') }}">
                            </a>
                        @elseif($document->file_path)
                            <a href="{{ asset($document->file_path) }}" target="_blank">{{ translate('View File') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
