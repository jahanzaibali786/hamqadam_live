@extends('frontend.layouts.app')

@section('content')
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">

                    <div class="d-flex align-items-center mb-4">
                        <a href="{{ route('wallet.index') }}" class="btn btn-soft-primary btn-sm mr-3">
                            <i class="las la-arrow-left"></i> {{ translate('Back') }}
                        </a>
                        <h3 class="fs-20 fw-700 mb-0">{{ translate('Buy Custom Coins') }}</h3>
                    </div>

                    <div class="row">
                        <div class="col-lg-5 mb-4">
                            <div class="card shadow-none border h-100">
                                <div class="card-body text-center d-flex flex-column justify-content-center">
                                    <i class="las la-coins fs-60 text-primary mb-2"></i>
                                    <h4 class="fw-700 mb-1">{{ translate('Price per coin') }}</h4>
                                    <p class="fs-30 fw-800 text-primary mb-2">
                                        {{ single_price($unit_price) }}
                                    </p>
                                    <p class="text-muted mb-0 fs-14">
                                        {{ translate('Enter how many coins you need — the total is calculated automatically and charged once.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <form action="{{ route('custom_coins.purchase') }}" class="form-default" role="form"
                                method="POST" id="custom-coins-form">
                                @csrf
                                <input type="hidden" id="payment_type" value="">
                                <input type="hidden" id="unit_price" value="{{ $unit_price }}">

                                <div class="card shadow-none border">
                                    <div class="card-header p-3">
                                        <h3 class="fs-16 fw-600 mb-0">{{ translate('Select a payment option') }}</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group row">
                                            <label class="col-md-3 col-form-label">{{ translate('Coins') }}<span
                                                    class="text-danger"> *</span></label>
                                            <div class="col-md-9">
                                                <input type="number" name="coins" id="coins" min="1" step="1"
                                                    class="form-control" inputmode="numeric"
                                                    placeholder="{{ translate('e.g. 100') }}" required>
                                                <small class="text-muted">{{ translate('Whole numbers only — no decimals.') }}</small>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-md-3 col-form-label">{{ translate('Total') }}</label>
                                            <div class="col-md-9">
                                                <p class="fs-20 fw-700 mb-0" id="total-amount">{{ single_price(0) }}</p>
                                            </div>
                                        </div>

                                        <div class="row gutters-10">
                                            @if (get_setting('paypal_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paypal" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paypal.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Paypal') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('stripe_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="stripe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/stripe.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Stripe') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('razorpay_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="razorpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/rozarpay.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Razorpay') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paystack_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paystack" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paystack.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Paystack') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('instamojo_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="instamojo" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/instamojo.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Instamojo') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('sslcommerz_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="sslcommerz" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/sslcommerz.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Sslcommerz') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('phonepe_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="phonepe" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/phonepe.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('PhonePe') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('aamarpay_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="aamarpay" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/aamarpay.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Aamarpay') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('paytm_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="paytm" class="online_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/paytm.png') }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('Paytm') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('easypaisa_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="easypaisa" class="pakistan_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/easypaisa.png') }}" class="img-fluid mb-2"
                                                                onerror="this.style.display='none'">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('EasyPaisa') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif
                                            @if (get_setting('jazzcash_payment_activation') == 1)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="jazzcash" class="pakistan_payment" type="radio" name="payment_option">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ static_asset('assets/img/payment_method/jazzcash.png') }}" class="img-fluid mb-2"
                                                                onerror="this.style.display='none'">
                                                            <span class="d-block text-center fw-600 fs-15">{{ translate('JazzCash') }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endif

                                            @foreach ($manual_payments ?? [] as $method)
                                                <div class="col-4 col-md-3">
                                                    <label class="aiz-megabox d-block mb-3">
                                                        <input value="manual_payment" class="manual_payment" type="radio"
                                                            name="payment_option" data-method-id="{{ $method->id }}"
                                                            onchange="toggleManualPaymentData({{ $method->id }})">
                                                        <span class="d-block p-3 aiz-megabox-elem">
                                                            <img src="{{ uploaded_asset($method->photo) }}" class="img-fluid mb-2">
                                                            <span class="d-block text-center fw-600 fs-15">{{ $method->heading }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                        @foreach ($manual_payments ?? [] as $method)
                                            <div id="manual_payment_info_{{ $method->id }}" class="d-none">
                                                <div>@php echo $method->description @endphp</div>
                                            </div>
                                        @endforeach

                                        <div id="manual_payment_description" class="manual_payment_description d-none mb-3">
                                            <div class="p-3 bg-light rounded"></div>
                                        </div>

                                        <div id="purchase_by_manual_payment" class="d-none">
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Transaction ID') }}</label>
                                                <div class="col-md-9">
                                                    <input type="text" name="transaction_id" id="transaction_id" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Payment proof') }}</label>
                                                <div class="col-md-9">
                                                    <input type="file" name="payment_proof" id="payment_proof" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Details') }}</label>
                                                <div class="col-md-9">
                                                    <textarea name="payment_details" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="purchase_by_pakistan_payment" class="d-none">
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Mobile number') }}</label>
                                                <div class="col-md-9">
                                                    <input type="text" name="pakistan_mobile_number" id="pakistan_mobile_number" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Transaction ID') }}</label>
                                                <div class="col-md-9">
                                                    <input type="text" name="pakistan_transaction_id" id="pakistan_transaction_id" class="form-control">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-md-3 col-form-label">{{ translate('Note') }}</label>
                                                <div class="col-md-9">
                                                    <textarea name="pakistan_payment_note" class="form-control" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button type="button" class="btn btn-primary purchase_button" disabled
                                                onclick="customCoinsPurchase(this)">
                                                {{ translate('Proceed to Payment') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script type="text/javascript">
        function updateTotal() {
            var coins = parseInt($('#coins').val(), 10);
            var unit = parseFloat($('#unit_price').val());
            if (!isNaN(coins) && coins > 0 && !isNaN(unit)) {
                $('#total-amount').text((coins * unit).toFixed(2));
            } else {
                $('#total-amount').text('0.00');
            }
        }
        $('#coins').on('input change', updateTotal);

        $(".online_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $('#purchase_by_pakistan_payment').addClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('online_payment');
        });
        $(".pakistan_payment").click(function() {
            $('.manual_payment_description').addClass('d-none');
            $('#purchase_by_manual_payment').addClass('d-none');
            $('#purchase_by_pakistan_payment').removeClass('d-none');
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('pakistan_payment');
        });
        $(".manual_payment").click(function() {
            $(".purchase_button").prop('disabled', false);
            $("#payment_type").val('manual_payment');
            $('.manual_payment_description').removeClass('d-none');
            $('#purchase_by_pakistan_payment').addClass('d-none');
        });

        function toggleManualPaymentData(id) {
            $('#manual_payment_description').removeClass('d-none');
            $('#manual_payment_description .bg-light').html($('#manual_payment_info_' + id).html());
            $('#purchase_by_manual_payment').removeClass('d-none');
        }

        function customCoinsPurchase(el) {
            var coins = parseInt($('#coins').val(), 10);
            if (isNaN(coins) || coins < 1 || String(coins) !== String($('#coins').val()).trim()) {
                AIZ.plugins.notify('danger', '{{ translate("Please enter a whole number of coins (no decimals).") }}');
                return;
            }
            $(el).prop('disabled', true);
            $('#custom-coins-form').submit();
        }
    </script>
@endsection
