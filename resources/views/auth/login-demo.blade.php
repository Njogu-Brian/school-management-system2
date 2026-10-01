@extends('layouts.app')

@section('content')
@php
    $schoolName = isset($settings['school_name']) && $settings['school_name'] ? $settings['school_name']->value : 'EduLynk Demo Academy';
    $schoolLogo = isset($settings['school_logo']) && $settings['school_logo'] ? $settings['school_logo']->value : null;
    $personas = $demoPersonas ?? [];
@endphp

<style>
    body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Manrope', 'Poppins', system-ui, sans-serif;
        background: #0b1f33;
    }
    .demo-login-shell {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(320px, 420px) 1fr;
    }
    .demo-login-panel {
        background: #fff;
        padding: 2rem 1.75rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .demo-login-panel .logo {
        max-height: 56px;
        margin-bottom: 0.75rem;
    }
    .demo-login-panel h1 {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0b1f33;
        margin: 0 0 0.25rem;
    }
    .demo-login-panel .eyebrow {
        font-size: 0.75rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #1769ff;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }
    .demo-login-aside {
        position: relative;
        background:
            linear-gradient(160deg, rgba(11, 31, 51, 0.92), rgba(23, 105, 255, 0.55)),
            url('{{ public_image_url("page background.jpg") }}') center/cover;
        color: #fff;
        padding: 3rem 2.5rem;
        display: flex;
        align-items: center;
    }
    .demo-login-aside h2 {
        font-size: clamp(1.6rem, 2.5vw, 2.25rem);
        font-weight: 800;
        margin-bottom: 1rem;
    }
    .demo-news {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        max-width: 520px;
    }
    .demo-news li + li { margin-top: 0.85rem; }
    .demo-news .muted { color: rgba(255,255,255,0.72); font-size: 0.92rem; }
    .demo-quick-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.55rem;
        margin-top: 0.85rem;
    }
    .demo-quick-btn {
        border: 0;
        border-radius: 8px;
        color: #fff;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 0.7rem 0.5rem;
        transition: transform 0.15s ease, filter 0.15s ease;
    }
    .demo-quick-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        filter: brightness(1.08);
        color: #fff;
    }
    .demo-quick-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .demo-alt-links {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid #e5e7eb;
        font-size: 0.85rem;
    }
    .demo-alt-links a {
        color: #475569;
        text-decoration: none;
    }
    .demo-alt-links a:hover { color: #1769ff; text-decoration: underline; }
    .demo-note {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 0.65rem;
    }
    @media (max-width: 900px) {
        .demo-login-shell { grid-template-columns: 1fr; }
        .demo-login-aside { min-height: 280px; padding: 2rem 1.5rem; }
    }
</style>

<div class="demo-login-shell">
    <aside class="demo-login-panel text-start">
        @if ($schoolLogo && file_exists(public_images_path($schoolLogo)))
            <img src="{{ public_image_url($schoolLogo) }}" alt="Logo" class="logo">
        @else
            <img src="{{ public_image_url('logo.png') }}" alt="EduLynk" class="logo">
        @endif

        <p class="eyebrow">Sandbox demo</p>
        <h1>{{ $schoolName }}</h1>
        <p class="text-muted small mb-3">Explore the full school ERP with sample data. One-click sign-in for common roles.</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('status'))
            <div class="alert alert-success py-2 small">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mb-2">
            @csrf
            <div class="mb-2">
                <label class="form-label small fw-semibold mb-1">Email or username</label>
                <input type="text" class="form-control" name="identifier" value="{{ old('identifier', old('email')) }}" required autofocus autocomplete="username">
            </div>
            <div class="mb-2">
                <label class="form-label small fw-semibold mb-1">Password</label>
                <input type="password" class="form-control" name="password" required autocomplete="current-password">
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="remember" id="rememberDemo" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small" for="rememberDemo">Remember Me</label>
                </div>
                <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary w-100" style="background:#1769ff;border:0;">Sign In</button>
        </form>

        <p class="small fw-semibold text-uppercase text-muted mb-1" style="letter-spacing:0.08em;">Quick demo login</p>
        <div class="demo-quick-grid">
            @foreach ($personas as $persona)
                <form method="POST" action="{{ route('demo.login-as', $persona['key']) }}">
                    @csrf
                    <button
                        type="submit"
                        class="demo-quick-btn w-100"
                        style="background: {{ $persona['color'] }};"
                        @disabled(! $persona['available'])
                        title="{{ $persona['available'] ? 'Sign in as '.$persona['label'] : 'No account for this role in demo data' }}"
                    >
                        {{ $persona['label'] }}
                    </button>
                </form>
            @endforeach
        </div>
        <p class="demo-note">* Tip: use Super Admin for the full console. Password for manual login: <code>Demo@12345</code> (admin@demo.school).</p>

        <div class="demo-alt-links">
            <a href="#" onclick="return demoFeatureNotice('OTP login');">Login with OTP</a>
            <a href="#" onclick="return demoFeatureNotice('Passkeys');">Sign in with Passkey</a>
            <a href="#" onclick="return demoFeatureNotice('Google sign-in');">Continue with Google</a>
        </div>
        <p class="demo-note mb-0">OTP, Passkeys and Google are shown for product demo only — use quick login or password on this sandbox.</p>
    </aside>

    <section class="demo-login-aside">
        <div>
            <h2>What’s in this EduLynk demo</h2>
            <div class="demo-news">
                <ul class="list-unstyled mb-0">
                    <li>
                        <div class="fw-semibold">Students, finance &amp; academics</div>
                        <div class="muted">Browse realistic classes, invoices, attendance and report cards.</div>
                    </li>
                    <li>
                        <div class="fw-semibold">Role-based dashboards</div>
                        <div class="muted">Jump in as Super Admin, Teacher, Accountant and more with one click.</div>
                    </li>
                    <li>
                        <div class="fw-semibold">Safe sample data</div>
                        <div class="muted">Names and contacts are anonymised for showcase use.</div>
                    </li>
                    @forelse (($announcements ?? []) as $note)
                        <li>
                            @if(is_object($note) && !empty($note->title))
                                <div class="fw-semibold">{{ $note->title }}</div>
                                <div class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($note->content ?? ''), 120) }}</div>
                            @endif
                        </li>
                    @empty
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</div>

<script>
    function demoFeatureNotice(feature) {
        alert(feature + ' is available on live EduLynk school sites. On this public sandbox, use Quick demo login or email/password instead.');
        return false;
    }
</script>
@endsection
