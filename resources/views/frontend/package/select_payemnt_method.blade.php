@extends('frontend.layouts.app')
@section('content')
    <section class="py-5 bg-white">
        <div class="container">
        @if(session('error'))
        <div class="alert alert-danger text-center   ">
            {{ session('error') }}
        </div>
        @endif
            <div class="row">
                <div class="col-xxl-3 col-xl-4">
                    <div class="card shadow-none overflow-hidden">
                        <div class="card-body">
                            <div class="text-center mb-4 mt-3">
                                <img class="mw-100 mx-auto mb-4" src="{{ uploaded_asset($package->image) }}" height="130">
                                <h5 class="mb-3 h5 fw-600">{{ $package->name }}</h5>
                            </div>
                            <ul class="list-group list-group-raw fs-15 mb-5">
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->express_interest }} {{ translate('Coins') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->photo_gallery }} {{ translate('Gallery Photo Upload') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->contact }} {{ translate('Contact Info View') }}
                                </li>
                                <li class="list-group-item py-2">
                                    <i class="las la-check text-success mr-2"></i>
                                    {{ $package->profile_viewers_view }} {{ translate('Profile Viewer View') }}
                                </li>
                                @if (get_setting('profile_picture_privacy') == 'only_me')
                                    <li class="list-group-item py-2">
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ $package->profile_image_view }} {{ translate('Profile Image View') }}
                                    </li>
                                @endif
                                @if (get_setting('gallery_image_privacy') == 'only_me')
                                    <li class="list-group-item py-2">
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ $package->gallery_image_view }} {{ translate('Gallery Image View') }}
                                    </li>
                                @endif
                                <li class="list-group-item py-2 text-line-through">
                                    @if ($package->auto_profile_match == 0)
                                        <i class="las la-times text-danger mr-2"></i>
                                        <del class="opacity-60">{{ translate('Show Auto Profile Match') }}</del>
                                    @else
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ translate('Show Auto Profile Match') }}
                                    @endif
                                </li>
                                <li class="list-group-item py-2 text-line-through">
                                    @if ($package->auto_horoscope_profile_match == 0)
                                        <i class="las la-times text-danger mr-2"></i>
                                        <del class="opacity-60">{{ translate('Show Auto Horoscope Profile Match') }}</del>
                                    @else
                                        <i class="las la-check text-success mr-2"></i>
                                        {{ translate('Show Auto Horoscope Profile Match') }}
                                    @endif
                                </li>
                            </ul>
                            <div class="mb-5 text-dark text-center">
                                @php
                                    $package_price = $package->price;
                                @endphp
                                @if ($package->id == 1)
                                    <span class="display-4 fw-600 lh-1 mb-0">{{ translate('Free') }}</span>
                                @else
                                    @if (addon_activation('referral_system') && Auth::user()->referred_by != null && Auth::user()->referral_comission == 0)
                                        @php
                                            $discount_type = get_setting('referral_user_package_purchase_discount_type');
                                            if ($discount_type == 'percent') {
                                                $discount = ($package->price * get_setting('referral_user_package_purchase_discount')) / 100;
                                            } else {
                                                $discount = get_setting('referral_user_package_purchase_discount');
                                            }
                                            $package_price = $package_price - $discount;
                                        @endphp
                                        <del class="opacity-60">{{ single_price($package->price) }}</del>
                                        <br>
                                        <span class="display-4 fw-600 lh-1 mb-0">{{ single_price($package_price) }}</span>
                                    @else
                                        <span class="display-4 fw-600 lh-1 mb-0">{{ single_price($package_price) }}</span>
                                    @endif
                                @endif
                                <span class="text-secondary d-block">{{ $package->validity }}
                                    {{ translate('Days') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <form action="{{ route('package.payment') }}" class="form-default" role="form" method="POST"
                        id="package-payment-form">
                        @csrf
                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                        <input type="hidden" name="amount" value="{{ $package_price }}">
                        <input type="hidden" id="payment_type" value="">

                        <div class="card shadow-none">
                            <div class="card-header p-3">
                                <h3 class="fs-16 fw-600 mb-0">
                                    {{ translate('Select a payment option') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xxl-8 col-xl-10 mx-auto">
                                        <div class="row gutters-10">
                                            @if (get_setting('paypal_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paypal" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paypal.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Paypal') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('stripe_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="stripe" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/stripe.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Stripe') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('instamojo_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="instamojo" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/instamojo.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Instamojo') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('razorpay_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="razorpay" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/rozarpay.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Razorpay') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paystack_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paystack" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paystack.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Paystack') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paytm_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paytm" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paytm.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Paytm') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('aamarpay_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="aamarpay" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/aamarpay.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Aamarpay') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('sslcommerz_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="sslcommerz" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/sslcommerz.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Sslcommerz') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @if (get_setting('phonepe_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="phonepe" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/phonepe.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('PhonePe') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif


                                            @if (get_setting('wallet_system') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="wallet" class="online_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/wallet.png') }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ translate('Wallet') }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @if (get_setting('easypaisa_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="easypaisa" class="pakistan_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <i class="las la-mobile-alt la-3x text-success mb-2"></i>
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('EasyPaisa') }}</span>
                                                                <small>{{ translate('Admin verified') }}</small>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @if (get_setting('jazzcash_payment_activation') == 1)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="jazzcash" class="pakistan_payment" type="radio"
                                                            name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <i class="las la-wallet la-3x text-warning mb-2"></i>
                                                            <span class="d-block text-center">
                                                                <span class="d-block fw-600 fs-15">{{ translate('JazzCash') }}</span>
                                                                <small>{{ translate('Admin verified') }}</small>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @foreach ($manual_payments as $method)
                                                <div class="col-6 col-md-4">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="manual_payment" class="manual_payment"
                                                            id="payment_option" type="radio"
                                                            data-method-id="{{ $method->id }}" name="payment_option"
                                                            onchange="toggleManualPaymentData({{ $method->id }})">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ uploaded_asset($method->photo) }}"
                                                                class="img-fluid mb-2">
                                                            <span class="d-block text-center">
                                                                <span
                                                                    class="d-block fw-600 fs-15">{{ $method->heading }}</span>
                                                            </span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach

                                            {{--  --}}
                                        </div>
                                    </div>
                                    {{--  --}}
                                    @foreach ($manual_payments as $method)
                                        <div id="manual_payment_info_{{ $method->id }}" class="d-none col-6 col-md-4">

                                            <div>@php echo $method->description @endphp</div>
                                            @if ($method->bank_info != null)
                                                <ul>
                                                    @foreach (json_decode($method->bank_info) as $key => $info)
                                                        <li style="list-style: none ">
                                                            {{ translate('Bank Name') }} -{{ $info->bank_name }}
                                                        </li>
                                                        <li style="list-style: none ">
                                                            {{ translate('Account Name') }} - {{ $info->account_name }},
                                                        </li>
                                                        <li style="list-style: none ">
                                                            {{ translate('Account Number') }} -{{ $info->account_number }}
                                                        </li>
                                                        <li style="list-style: none ">
                                                            {{ translate('Routing Number') }} -
                                                            {{ $info->routing_number }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach


                                    <div class="card mb-3 p-3 d-none w-100 manual_payment_description">
                                        <div id="manual_payment_description">

                                        </div>
                                    </div>

                                    <div class="col-md-12 d-none" id="purchase_by_manual_payment">
                                        <input type="hidden" name='manual_payment_id' class="manual_payment_id">
                                        <div class="form-group row">
                                            <div class="col-md-6">
                                                <label>{{ translate('Transaction Id') }}<span class="text-danger">
                                                        *</span></label>
                                                <input type="text" name="transaction_id" id="transaction_id"
                                                    class="form-control" placeholder="{{ translate('Transaction Id') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>{{ translate('Payment Proof') }}<span class="text-danger">
                                                        *</span></label>
                                                <div class="input-group" data-toggle="aizuploader" data-type="image">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text bg-soft-secondary font-weight-medium">
                                                            {{ translate('Browse') }}</div>
                                                    </div>
                                                    <div class="form-control file-amount">{{ translate('Choose File') }}
                                                    </div>
                                                    <input type="hidden" name="payment_proof" id="payment_proof"
                                                        class="selected-files">
                                                </div>
                                                <div class="file-preview box sm">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-12">
                                                <label>{{ translate('Payment Details') }}</label>
                                                <textarea type="text" name="payment_details" class="form-control" rows="2"
                                                    placeholder="{{ translate('Payment Details') }}"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12 d-none" id="purchase_by_pakistan_payment">
                                        <div class="alert alert-info">
                                            {{ translate('Submit your EasyPaisa/JazzCash mobile number and transaction reference. Admin will verify and activate your package.') }}
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-6">
                                                <label>{{ translate('Mobile Number') }}<span class="text-danger">*</span></label>
                                                <input type="text" name="pakistan_mobile_number" id="pakistan_mobile_number"
                                                    class="form-control" placeholder="{{ translate('03XXXXXXXXX') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label>{{ translate('Transaction Id / Reference') }}</label>
                                                <input type="text" name="pakistan_transaction_id" id="pakistan_transaction_id"
                                                    class="form-control" placeholder="{{ translate('Transaction Id') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-md-12">
                                                <label>{{ translate('Payment Note') }}</label>
                                                <textarea name="pakistan_payment_note" class="form-control" rows="2"
                                                    placeholder="{{ translate('Sender name, account number, or any helpful note') }}"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=" text-right pt-3">
                            <button type="button" onclick="package_purchase(this)"
                                class="btn btn-primary fw-600 purchase_button"
                                disabled>{{ translate('Confirm') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        // $(document).ready(function() {
        $(".online_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $('#purchase_by_pakistan_payment').addClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('online_payment');
        });
        $(".manual_payment").click(function() {
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('manual_payment');
            $('.manual_payment_description').removeClass('d-none');
            $('#purchase_by_pakistan_payment').addClass('d-none');
        });
        $(".pakistan_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $('#purchase_by_pakistan_payment').removeClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('pakistan_payment');
        });

        function package_purchase(el) {
            $(el).prop('disabled', true);
            var payment_type = $("#payment_type").val();
            if (payment_type == 'manual_payment') {
                var transaction_id = $("#transaction_id").val();
                var payment_proof = $("#payment_proof").val();
                if (transaction_id == '' || payment_proof == '') {
                    AIZ.plugins.notify('danger', '{{ translate('Please Provide transaction id and payemnt proof.') }}');
                    $(el).prop('disabled', false);
                } else {
                    $('#package-payment-form').submit();
                }
            } else if (payment_type == 'pakistan_payment') {
                var pakistan_mobile_number = $("#pakistan_mobile_number").val();
                if (pakistan_mobile_number == '') {
                    AIZ.plugins.notify('danger', '{{ translate('Please provide your mobile number.') }}');
                    $(el).prop('disabled', false);
                } else {
                    $('#package-payment-form').submit();
                }
            } else {
                $('#package-payment-form').submit();
            }
        }
        // manual payment
        function toggleManualPaymentData(id) {
            $('#manual_payment_description').parent().removeClass('d-none');
            $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            $('#purchase_by_manual_payment').removeClass('d-none');
            $('.manual_payment_id').val(id)

        }
    </script>
@endsection
