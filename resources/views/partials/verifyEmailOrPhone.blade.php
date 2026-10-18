<script type="text/javascript">
(function () {
    const otpInput = document.getElementById('verification_code');
    const verifyBtn = document.getElementById('verifyOtpBtn');
    const otpGroup = $('#otpInputGroup');
    let requestSequence = 0;
    let pendingIdentifier = null;
    let verifiedIdentifier = null;

    function emailValue() {
        return String($('#signinSrEmailVerify').val() || $('#signinSrEmail').val() || $('#signinAddonEmail').val() || '').trim().toLowerCase();
    }
    function identifier() {
        return emailValue() ? 'email:' + emailValue() : 'phone:' + String($('#phone-code').val() || '').replace(/\D/g, '');
    }
    function payload() {
        return {
            _token: '{{ csrf_token() }}',
            email: emailValue(),
            phone: String($('#phone-code').val() || '').trim(),
            country_code: String($('input[name="country_code"]').val() || '').trim()
        };
    }
    function notify(kind, message) {
        if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) AIZ.plugins.notify(kind, message);
    }
    function refreshSubmit() {
        if (typeof toggleCreateBtn === 'function') toggleCreateBtn();
    }
    function resetVerification(hideGroup) {
        requestSequence++;
        pendingIdentifier = null;
        verifiedIdentifier = null;
        if (otpInput) {
            otpInput.disabled = false;
            otpInput.value = '';
        }
        if (verifyBtn) {
            verifyBtn.classList.remove('disabled');
            verifyBtn.style.backgroundColor = '';
            verifyBtn.innerHTML = '<i class="las la-lg la-arrow-right"></i>';
        }
        if (hideGroup) otpGroup.addClass('d-none').removeClass('d-block');
        refreshSubmit();
    }

    window.sendVerificationCode = function (clickedBtn) {
        const btn = clickedBtn || document.getElementById('sendOtpBtn');
        const data = payload();
        const target = identifier();
        if (!data.email && !data.phone) {
            notify('danger', '{{ translate("Please enter your email or phone number") }}');
            return;
        }
        resetVerification(true);
        const sequence = requestSequence;
        const originalText = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; }
        $.post('{{ route("verification_code_send") }}', data).done(function (response) {
            if (sequence !== requestSequence || target !== identifier()) return;
            notify(response.status === 1 ? 'success' : 'danger', response.message);
            if (response.status === 1) {
                pendingIdentifier = target;
                otpGroup.removeClass('d-none').addClass('d-block');
                $('#verification_code').closest('.form-group').removeClass('d-none').addClass('d-block');
                if (otpInput) otpInput.focus();
            }
        }).fail(function (xhr) {
            if (sequence === requestSequence) notify('danger', xhr.responseJSON?.message || 'Unable to send verification code.');
        }).always(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        });
    };

    if (otpInput) {
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
            if (this.value.length !== 6) return;
            const target = identifier();
            if (!pendingIdentifier || pendingIdentifier !== target) {
                resetVerification(true);
                notify('danger', 'Your email or phone changed. Request a new OTP.');
                return;
            }
            const sequence = requestSequence;
            const data = payload();
            data.code = this.value;
            if (verifyBtn) verifyBtn.innerHTML = '<i class="las la-lg la-spinner la-spin"></i>';
            $.post('{{ route("verify_code_confirmation") }}', data).done(function (response) {
                if (sequence !== requestSequence || target !== identifier()) return;
                if (response.status === 1) {
                    verifiedIdentifier = target;
                    otpInput.disabled = true;
                    if (verifyBtn) {
                        verifyBtn.innerHTML = '<i class="las la-lg la-check-circle text-success"></i>';
                        verifyBtn.classList.add('disabled');
                        verifyBtn.style.backgroundColor = '#f7f8fa';
                    }
                    notify('success', response.message);
                } else {
                    if (verifyBtn) verifyBtn.innerHTML = '<i class="las la-lg la-times-circle text-danger"></i>';
                    notify('danger', response.message);
                }
                refreshSubmit();
            }).fail(function (xhr) {
                if (sequence === requestSequence) notify('danger', xhr.responseJSON?.message || 'OTP verification failed.');
                refreshSubmit();
            });
        });
    }

    $(document).on('input change', '#signinSrEmailVerify, #signinSrEmail, #signinAddonEmail, #phone-code, input[name="country_code"]', function () {
        if (pendingIdentifier !== null || verifiedIdentifier !== null || (verifyBtn && verifyBtn.classList.contains('disabled'))) {
            resetVerification(true);
        }
    });
})();
</script>
