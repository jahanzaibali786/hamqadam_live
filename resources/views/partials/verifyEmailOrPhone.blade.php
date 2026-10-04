<script type="text/javascript">
   function sendVerificationCode(clickedBtn = null) {
        let btn = clickedBtn ? clickedBtn : document.getElementById('sendOtpBtn');

        let rawEmail = $('#signinSrEmailVerify').length ? $('#signinSrEmailVerify').val() : ($('#signinSrEmail').length ? $('#signinSrEmail').val() : $('#signinAddonEmail').val());
        let rawPhone = $('#phone-code').length ? $('#phone-code').val() : '';
        let email = rawEmail ? rawEmail.trim() : '';
        let phone = rawPhone ? rawPhone.trim() : '';
        let country_code = ($('input[name="country_code"]').val() || '').trim();

        let identifier = email ? email : phone;

        if (!identifier) {
            AIZ.plugins.notify('danger', '{{ translate("Please enter your email or phone number") }}');
            return;
        }
        let otpGroup = $('#otpInputGroup');
        let codeGroup = $('#verification_code').closest('.form-group');

        let originalText = $(btn).html();
        $(btn).prop('disabled', true).text('Sending...');

        $.post('{{ route("verification_code_send") }}', {
            _token: '{{ csrf_token() }}',
            email: email,
            phone: phone,
            country_code: country_code
        }, function (data) {
            if (data.status == 2) {
                AIZ.plugins.notify('danger', `${data.message}`);
            } else if (data.status == 1) {
                AIZ.plugins.notify('success', `${data.message}`);

                otpGroup.removeClass('d-none').addClass('d-block');
                codeGroup.removeClass('d-none').addClass('d-block');
            } else {
                AIZ.plugins.notify('danger', `${data.message}`);
            }
        })
        .always(function () {
            $(btn).prop('disabled', false).html(originalText);
        });
    }

    const codeInput = document.getElementById('verification_code');
    const verifyBtn = document.getElementById('verifyOtpBtn');

    //realtime validation
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 6) this.value = this.value.slice(0,6);

            if (this.value.length === 6) {
                verifyBtn.innerHTML = '<i class="las la-lg la-spinner la-spin"></i>';

                let rawEmail = $('#signinSrEmailVerify').length ? $('#signinSrEmailVerify').val() : ($('#signinSrEmail').length ? $('#signinSrEmail').val() : $('#signinAddonEmail').val());
                let rawPhone = $('#phone-code').length ? $('#phone-code').val() : '';
                let email = rawEmail ? rawEmail.trim() : '';
                let phone = rawPhone ? rawPhone.trim() : '';
                let country_code = ($('input[name="country_code"]').val() || '').trim();

                $.post('{{ route("verify_code_confirmation") }}', {
                    _token: '{{ csrf_token() }}',
                    code: this.value,
                    email: email,  
                    phone: phone,
                    country_code: country_code
                }, function(data) {
                    if(data.status === 1){
                        verifyBtn.innerHTML = '<i class="las la-lg la-check-circle text-success"></i>';
                        AIZ.plugins.notify('success', `${data.message}`);
                        codeInput.disabled = true;
                        verifyBtn.classList.add('disabled');
                        verifyBtn.style.backgroundColor = '#f7f8fa';
                        if (typeof toggleCreateBtn === 'function') {
                            toggleCreateBtn();
                        }
                    } else {
                        AIZ.plugins.notify('danger', `${data.message}`);
                        verifyBtn.innerHTML = '<i class="las la-lg la-times-circle text-danger"></i>';
                        if (typeof toggleCreateBtn === 'function') {
                            toggleCreateBtn();
                        }
                    }
                });
            } else {
                verifyBtn.innerHTML = '<i class="las la-lg la-arrow-right"></i>';
            }
        });
    }

    // If email or phone changes after verification, reset verified state
    $(document).on('input', '#signinSrEmailVerify, #signinSrEmail, #phone-code', function() {
        if (codeInput && codeInput.disabled) {
            codeInput.disabled = false;
            codeInput.value = '';
            if (verifyBtn) {
                verifyBtn.classList.remove('disabled');
                verifyBtn.style.backgroundColor = '';
                verifyBtn.innerHTML = '<i class="las la-lg la-arrow-right"></i>';
            }
            if (typeof toggleCreateBtn === 'function') {
                toggleCreateBtn();
            }
        }
    });
</script>