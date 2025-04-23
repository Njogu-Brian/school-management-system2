@extends('layouts.app')

@section('content')
<style>
    body {
        background: url('{{ asset('storage/' . ($settings['login_background']->value ?? 'default-bg.jpg')) }}') no-repeat center center fixed;
        background-size: cover;
    }
    .login-wrapper {
        background-color: rgba(255, 255, 255, 0.95);
        padding: 2rem;
        border-radius: 12px;
        max-width: 500px;
        margin: auto;
        margin-top: 5%;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .school-logo {
        height: 60px;
        margin-bottom: 15px;
    }
    @media (max-width: 768px) {
        .login-wrapper {
            margin-top: 20%;
        }
    }
</style>

<div class="container">
    <div class="login-wrapper text-center">
        @if(isset($settings['school_logo']))
            <img src="{{ asset('storage/' . $settings['school_logo']->value) }}" class="school-logo" alt="School Logo">
        @endif
        <h4 class="mb-4">{{ $settings['school_name']->value ?? 'School Management System' }}</h4>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-3 text-start">
                <label for="email" class="form-label">Email Address</label>
                <input id="email" type="email" class="form-control" name="email" required autofocus>
            </div>

            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" class="form-control" name="password" required>
            </div>

            <div class="mb-3 form-check text-start">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label" for="remember">Remember Me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Login</button>

            <div class="mt-3">
                <a href="{{ route('password.request') }}">Forgot Your Password?</a>
            </div>
        </form>

        <hr class="my-4">

        <div class="text-start">
            <h6>📢 Announcements</h6>
            <ul class="list-unstyled small">
                <li>• Term 2 begins on May 1st 🎓</li>
                <li>• Transport routes updated 🚍</li>
                <li>• New kitchen menu available 🍛</li>
            </ul>
        </div>
    </div>
</div>
@endsection
