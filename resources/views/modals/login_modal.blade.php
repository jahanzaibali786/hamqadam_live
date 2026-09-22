<div class="modal fade hq-login-modal" id="LoginModal">
    <div class="modal-dialog modal-dialog-centered modal-dialog-zoom">
        <div class="modal-content hq-login-modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ translate('Welcome Back') }}</h5>
                    <p class="mb-0">{{ translate('Login to continue finding meaningful connections.') }}</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="email">
                            {{ addon_activation('otp_system') ? translate('Email/Phone') : translate('Email') }}
                        </label>
                        @if (addon_activation('otp_system'))
                            <input type="text" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{ translate('Email Or Phone') }}" name="email" id="email">
                            <small class="form-text">{{ translate('Use country code before number') }}</small>
                        @else
                            <input type="email" class="form-control {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{ translate('Email') }}" name="email" id="email">
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">{{ translate('Password') }}</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="********" required>
                        @error('password')
                            <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4 text-right">
                        <a class="link-muted text-capitalize font-weight-normal" href="{{ route('password.request') }}">{{ translate('Forgot Password?') }}</a>
                    </div>

                    <button type="submit" class="btn btn-block btn-primary">{{ translate('Login to your Account') }}</button>
                </form>

                @if (env('DEMO_MODE') == 'On')
                    <div class="mb-4 mt-4">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>user2@example.com</td>
                                    <td>12345678</td>
                                    <td><button class="btn btn-outline-primary btn-xs" onclick="autoFill1()">{{ translate('Copy') }}</button></td>
                                </tr>
                                <tr>
                                    <td>user17@example.com</td>
                                    <td>12345678</td>
                                    <td><button class="btn btn-outline-primary btn-xs" onclick="autoFill2()">{{ translate('Copy') }}</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="hq-modal-register text-center">
                    <p class="text-muted mb-1">{{ translate("Don't have an account?") }}</p>
                    <a href="{{ route('register') }}">{{ translate('Create an account') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>