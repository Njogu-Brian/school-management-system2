@extends('layouts.app')

@section('content')
@php
    $schoolName = isset($settings['school_name']) && $settings['school_name'] ? $settings['school_name']->value : 'School Management System';
    $schoolLogo = isset($settings['school_logo']) && $settings['school_logo'] ? $settings['school_logo']->value : null;
    $loginBg    = isset($settings['login_background']) && $settings['login_background'] ? $settings['login_background']->value : null;

    // Use public_images_path / public_image_url so ASSET_URL and PUBLIC_WEB_ROOT work in production
    $bgImage = null;
    if ($loginBg) {
        if (file_exists(public_images_path($loginBg))) {
            $bgImage = public_image_url($loginBg);
        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($loginBg)) {
            $bgImage = \Illuminate\Support\Facades\Storage::url($loginBg);
        }
    }
    if (!$bgImage) {
        $fallbackImages = ['page background.jpg', '1757052514_page background.jpg'];
        foreach ($fallbackImages as $fallback) {
            if (file_exists(public_images_path($fallback))) {
                $bgImage = public_image_url($fallback);
                break;
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($fallback)) {
                $bgImage = \Illuminate\Support\Facades\Storage::url($fallback);
                break;
            }
        }
    }
@endphp

<style>
    body {
        @if($bgImage)
        background: url('{{ $bgImage }}') no-repeat center center fixed;
        background-size: cover;
        @else
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        @endif
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }

    .login-box {
        background: rgba(255, 255, 255, 0.96);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        max-width: 420px;
        width: 90%;
        margin: 20px;
        animation: fadeIn 0.6s ease-in-out;
    }

    .login-box img.logo {
        max-height: 70px;
        margin-bottom: 15px;
    }

    .login-box h5 {
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
    }

    .login-box label {
        font-weight: 500;
        margin-bottom: 5px;
        color: #444;
    }

    .login-box input.form-control {
        border-radius: 8px;
        padding: 10px;
        font-size: 14px;
    }

    .btn-primary {
        background: #007bff;
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #0056b3;
        transform: translateY(-1px);
    }

    .announcements {
        background: #f9f9f9;
        border-left: 4px solid #007bff;
        padding: 12px;
        margin-top: 20px;
        font-size: 14px;
        border-radius: 6px;
        text-align: left;
    }

    .alert {
        font-size: 14px;
        border-radius: 6px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ✅ Responsive adjustments */
    @media (max-width: 576px) {
        .login-box {
            padding: 20px;
        }
        .login-box img.logo {
            max-height: 55px;
        }
        .btn-primary {
            padding: 10px;
            font-size: 14px;
        }
    }
</style>

<div class="login-box text-center">
    {{-- Logo: use public_image_url so ASSET_URL works when public files are on another domain --}}
    @if ($schoolLogo && file_exists(public_images_path($schoolLogo)))
        <img src="{{ public_image_url($schoolLogo) }}" alt="Logo" class="logo">
    @else
        <img src="{{ public_image_url('logo.png') }}" alt="Default Logo" class="logo">
    @endif

    {{-- ✅ School name --}}
    <h5>{{ $schoolName }}</h5>

    {{-- ✅ Show validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger text-start">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ✅ Show status messages --}}
    @if (session('status'))
        <div class="alert alert-success text-start">
            {{ session('status') }}
        </div>
    @endif

    {{-- ✅ OTP Login Form (shown when OTP is requested) --}}
    @if(session('otp_sent'))
        <form method="POST" action="{{ route('login') }}" class="text-start" id="otpLoginForm">
            @csrf
            <input type="hidden" name="email" value="{{ session('otp_email') }}">
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> OTP sent to phone ending in <strong>{{ session('otp_phone') }}</strong>
            </div>

            <div class="mb-3">
                <label>Enter OTP Code</label>
                <input type="text" class="form-control text-center" name="otp_code" 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus
                       style="font-size: 24px; letter-spacing: 8px;">
                <small class="text-muted">6-digit code sent via SMS</small>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label">Remember Me</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Verify & Login</button>
            <button type="button" class="btn btn-outline-secondary w-100" onclick="showPasswordForm()">Use Password Instead</button>
        </form>
    @else
        {{-- ✅ Standard Login Form --}}
        <form method="POST" action="{{ route('login') }}" class="text-start" id="passwordLoginForm">
            @csrf
            <div class="mb-3">
                <label>Email Address</label>
                <input type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="mb-3">
                <label>Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label">Remember Me</label>
                </div>
                <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Login</button>
            <button type="button" class="btn btn-outline-info w-100" onclick="requestOTP()">Login with OTP</button>
        </form>

        {{-- ✅ Hidden OTP Request Form --}}
        <form method="POST" action="{{ route('login') }}" class="d-none" id="otpRequestForm">
            @csrf
            <input type="hidden" name="request_otp" value="1">
            <input type="hidden" name="email" id="otpRequestEmail">
        </form>
    @endif

    <script>
        function requestOTP() {
            const email = document.querySelector('#passwordLoginForm input[name="email"]').value;
            if (!email) {
                alert('Please enter your email address first.');
                return;
            }
            document.getElementById('otpRequestEmail').value = email;
            document.getElementById('otpRequestForm').submit();
        }

        function showPasswordForm() {
            window.location.reload();
        }
    </script>

    {{-- ✅ Announcements --}}
    <div class="announcements mt-4">
        <strong>📢 Announcements:</strong>
        <ul class="mb-0">
            @forelse ($announcements as $note)
                <li>{{ $note }}</li>
            @empty
                <li>No current announcements</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
