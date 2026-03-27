@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" id="resetRequestForm">
                        @csrf

                        <div class="row mb-3">
                            <label for="identifier" class="col-md-4 col-form-label text-md-end">{{ __('Work Email or Phone') }}</label>

                            <div class="col-md-6">
                                <input id="identifier" type="text" class="form-control @error('identifier') is-invalid @enderror" name="identifier" value="{{ old('identifier') }}" required autofocus>

                                @error('identifier')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary mb-2 w-100">
                                    {{ __('Send Email Reset Link') }}
                                </button>
                                <button type="button" class="btn btn-outline-secondary mb-2 w-100" onclick="sendSmsLink()">
                                    {{ __('Send SMS Reset Link') }}
                                </button>
                                <button type="button" class="btn btn-outline-info w-100" onclick="requestOTPReset()">
                                    {{ __('Reset with OTP') }}
                                </button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('password.email') }}" class="d-none" id="otpResetForm">
                        @csrf
                        <input type="hidden" name="method" value="otp">
                        <input type="hidden" name="identifier" id="otpResetIdentifier">
                    </form>

                    <form method="POST" action="{{ route('password.email') }}" class="d-none" id="smsLinkForm">
                        @csrf
                        <input type="hidden" name="method" value="sms_link">
                        <input type="hidden" name="identifier" id="smsLinkIdentifier">
                    </form>

                    <script>
                        function requestOTPReset() {
                            const identifier = document.getElementById('identifier').value;
                            if (!identifier) {
                                alert('Please enter your work email or phone first.');
                                return;
                            }
                            document.getElementById('otpResetIdentifier').value = identifier;
                            document.getElementById('otpResetForm').submit();
                        }

                        function sendSmsLink() {
                            const identifier = document.getElementById('identifier').value;
                            if (!identifier) {
                                alert('Please enter your work email or phone first.');
                                return;
                            }
                            document.getElementById('smsLinkIdentifier').value = identifier;
                            document.getElementById('smsLinkForm').submit();
                        }
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
