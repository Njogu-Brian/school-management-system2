@extends('layouts.app')

@section('content')
@php
    $schoolName = isset($settings['school_name']) && $settings['school_name'] ? $settings['school_name']->value : 'School Management System';
    $schoolLogo = isset($settings['school_logo']) && $settings['school_logo'] ? $settings['school_logo']->value : null;
    $primaryColor = setting('finance_primary_color', '#390754');
    $secondaryColor = setting('finance_secondary_color', '#7d2fca');
    $loginBackgrounds = array_values(array_filter($loginBackgrounds ?? []));
    $bgImage = $loginBackgrounds[0] ?? null;
@endphp

<style>
    body {
        @if(!$bgImage)
        background: linear-gradient(135deg, {{ $primaryColor }} 0%, {{ $secondaryColor }} 100%);
        @else
        background: #111;
        @endif
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
        position: relative;
        overflow-x: hidden;
    }

    .login-carousel {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        background: #111;
    }
    .login-carousel__slide {
        position: absolute;
        inset: 0;
        background-position: center center;
        background-size: cover;
        background-repeat: no-repeat;
        opacity: 0;
        transition: opacity 1.2s ease-in-out;
        transform: scale(1.04);
    }
    .login-carousel__slide.is-active {
        opacity: 1;
        transform: scale(1);
        transition: opacity 1.2s ease-in-out, transform 8s ease-out;
    }
    .login-carousel__veil {
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(17,17,17,0.12) 0%, rgba(57,7,84,0.32) 55%, rgba(57,7,84,0.5) 100%);
    }
    .login-carousel__dots {
        position: fixed;
        left: 50%;
        bottom: 18px;
        transform: translateX(-50%);
        z-index: 2;
        display: flex;
        gap: 8px;
        pointer-events: auto;
    }
    .login-carousel__dots button {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        border: 0;
        padding: 0;
        background: rgba(255,255,255,0.45);
        cursor: pointer;
    }
    .login-carousel__dots button.is-active {
        background: #fff;
        box-shadow: 0 0 0 2px rgba(255,255,255,0.35);
    }

    .login-box {
        position: relative;
        z-index: 1;
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
        background: {{ $primaryColor }};
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        filter: brightness(0.92);
        transform: translateY(-1px);
    }

    .announcements {
        background: #f9f9f9;
        border-left: 4px solid {{ $primaryColor }};
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

    .app-download {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        text-align: left;
        font-size: 14px;
    }
    .app-download-prominent {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
    }
    .app-username {
        background: #f3f4f6;
        border-radius: 8px;
        padding: 10px 12px;
        word-break: break-all;
    }
    .ios-note {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 13px;
        color: #334155;
        text-align: left;
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
        .login-carousel__veil {
            background: rgba(17,17,17,0.35);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .login-carousel__slide {
            transition: none;
            transform: none;
        }
    }
</style>

@if(count($loginBackgrounds) > 0)
<div class="login-carousel" id="loginCarousel" aria-hidden="true">
    @foreach($loginBackgrounds as $i => $url)
        <div class="login-carousel__slide {{ $i === 0 ? 'is-active' : '' }}"
             style="background-image: url('{{ $url }}');"
             data-index="{{ $i }}"></div>
    @endforeach
    <div class="login-carousel__veil"></div>
</div>
@if(count($loginBackgrounds) > 1)
<div class="login-carousel__dots" id="loginCarouselDots" role="tablist" aria-label="Background photos">
    @foreach($loginBackgrounds as $i => $url)
        <button type="button"
                class="{{ $i === 0 ? 'is-active' : '' }}"
                aria-label="Show background {{ $i + 1 }}"
                data-index="{{ $i }}"></button>
    @endforeach
</div>
@endif
@endif

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
    @if (session('status') && ! session('parent_use_app'))
        <div class="alert alert-success text-start">
            {{ session('status') }}
        </div>
    @endif

    @if (session('parent_use_app'))
        @include('auth.partials.app-download-cta', [
            'prominent' => true,
            'username' => session('parent_app_username'),
        ])
        <a href="{{ route('login', ['staff' => 1]) }}" class="d-inline-block mt-3 small text-decoration-none">Staff sign in</a>
    @else
    {{-- ✅ OTP Login Form (shown when OTP is requested) --}}
    @if(session('otp_sent'))
        <form method="POST" action="{{ route('login') }}" class="text-start" id="otpLoginForm">
            @csrf
            <input type="hidden" name="identifier" value="{{ session('otp_identifier') }}">
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> OTP sent to phone ending in <strong>{{ session('otp_phone') }}</strong>
                @php $otpChannel = session('otp_delivery_channel', 'sms'); @endphp
                @if($otpChannel === 'whatsapp')
                    <div class="mt-1 small">SMS service is currently unavailable — your code was sent via <strong>WhatsApp</strong>.</div>
                @endif
            </div>

            <div class="mb-3">
                <label>Enter OTP Code</label>
                <input type="text" class="form-control text-center" name="otp_code" 
                       placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus
                       style="font-size: 24px; letter-spacing: 8px;">
                <small class="text-muted">6-digit code sent via {{ ($otpChannel ?? 'sms') === 'whatsapp' ? 'WhatsApp' : 'SMS' }}</small>
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
                <label>Username</label>
                <input type="text" class="form-control" name="identifier" value="{{ old('identifier', old('email')) }}" required autofocus autocomplete="username">
            </div>

            <div class="mb-3">
                <label>Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="loginPassword" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label">Remember Me</label>
                </div>
                <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-2">Login</button>
            <button type="button" class="btn btn-outline-info w-100" onclick="showOtpRequestForm()">Login with OTP</button>
            <button type="button" class="btn btn-outline-dark w-100 mt-2" onclick="loginWithPasskey()">Sign in with Passkey</button>
            <a href="{{ route('auth.google.redirect') }}" class="btn btn-outline-danger w-100 mt-2">
                Continue with Google
            </a>
            <p class="text-muted small mt-2 mb-0">Staff only. Parents should use the mobile app. Google must match an existing account email.</p>
        </form>

        {{-- ✅ OTP Request Form (username/email/phone first, then request OTP) --}}
        <form method="POST" action="{{ route('login') }}" class="d-none text-start" id="otpRequestForm">
            @csrf
            <input type="hidden" name="request_otp" value="1">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" class="form-control" name="identifier" id="otpRequestIdentifier" value="{{ old('identifier', old('email')) }}" required autocomplete="username">
                <small class="text-muted">Enter your username, email, or phone number, then request an OTP</small>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-2">Request OTP</button>
            <button type="button" class="btn btn-outline-secondary w-100" onclick="showPasswordForm()">Use Password Instead</button>
        </form>
    @endif

    <div class="mt-2">
        @include('auth.partials.app-download-cta', ['prominent' => false])
    </div>
    @endif

    <script>
        // WebAuthn helper (Passkeys)
        // CDN helper from Laragear Webpass (no build step required)
        (function ensureWebpassLoaded() {
            if (window.Webpass) return;
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/@laragear/webpass@2/dist/webpass.js';
            s.defer = true;
            document.head.appendChild(s);
        })();

        async function loginWithPasskey() {
            try {
                if (!window.Webpass) {
                    alert('Passkeys are still loading. Please try again in a second.');
                    return;
                }
                if (window.Webpass.isUnsupported()) {
                    alert("Your browser doesn't support Passkeys (WebAuthn).");
                    return;
                }
                const result = await window.Webpass.assert('/webauthn/login/options', '/webauthn/login');
                if (result && result.success) {
                    window.location.reload();
                    return;
                }
                alert(result?.error || 'Passkey sign-in failed.');
            } catch (e) {
                alert('Passkey sign-in failed. Please use password/OTP.');
            }
        }

        function showOtpRequestForm() {
            const passwordForm = document.getElementById('passwordLoginForm');
            const otpForm = document.getElementById('otpRequestForm');
            if (passwordForm) passwordForm.classList.add('d-none');
            if (otpForm) otpForm.classList.remove('d-none');

            const existing = document.querySelector('#passwordLoginForm input[name="identifier"]')?.value || '';
            const otpIdentifier = document.getElementById('otpRequestIdentifier');
            if (otpIdentifier && !otpIdentifier.value) otpIdentifier.value = existing;
            otpIdentifier?.focus();
        }

        function showPasswordForm() {
            const passwordForm = document.getElementById('passwordLoginForm');
            const otpForm = document.getElementById('otpRequestForm');
            if (otpForm) otpForm.classList.add('d-none');
            if (passwordForm) passwordForm.classList.remove('d-none');
            document.querySelector('#passwordLoginForm input[name="password"]')?.focus();
        }

        document.getElementById('toggleLoginPassword')?.addEventListener('click', function () {
            const input = document.getElementById('loginPassword');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const icon = this.querySelector('i');
            if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>

    @unless(session('parent_use_app'))
    {{-- Announcements --}}
    <div class="announcements mt-4">
        <strong>Announcements</strong>
        <ul class="mb-0 mt-2">
            @forelse ($announcements as $note)
                <li class="mb-2">
                    @if(is_object($note) && !empty($note->title))
                        <div class="fw-semibold">{{ $note->title }}</div>
                        <div class="text-muted small">{{ \Illuminate\Support\Str::limit(strip_tags($note->content ?? ''), 160) }}</div>
                    @else
                        {{ is_object($note) ? ($note->content ?? '') : $note }}
                    @endif
                </li>
            @empty
                <li>No current announcements</li>
            @endforelse
        </ul>
    </div>
    @endunless
</div>

@if(count($loginBackgrounds) > 1)
<script>
(function () {
    var slides = Array.prototype.slice.call(document.querySelectorAll('#loginCarousel .login-carousel__slide'));
    var dots = Array.prototype.slice.call(document.querySelectorAll('#loginCarouselDots button'));
    if (slides.length < 2) return;

    var current = 0;
    var intervalMs = 6000;
    var timer = null;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach(function (slide, i) {
            slide.classList.toggle('is-active', i === current);
        });
        dots.forEach(function (dot, i) {
            dot.classList.toggle('is-active', i === current);
        });
    }

    function next() { show(current + 1); }

    function start() {
        if (reduceMotion) return;
        stop();
        timer = window.setInterval(next, intervalMs);
    }

    function stop() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            show(parseInt(dot.getAttribute('data-index'), 10) || 0);
            start();
        });
    });

    slides.slice(1).forEach(function (slide) {
        var url = (slide.style.backgroundImage || '').replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
        if (url) {
            var img = new Image();
            img.src = url;
        }
    });

    start();
})();
</script>
@endif
@endsection
