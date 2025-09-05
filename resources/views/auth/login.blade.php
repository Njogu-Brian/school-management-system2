@extends('layouts.app')

@section('content')
@php
    $schoolName = $settings['school_name']->value ?? 'School Management System';
    $schoolLogo = $settings['school_logo']->value ?? null;
    $loginBg    = $settings['login_background']->value ?? null;
@endphp

<style>
    body {
        background: url('{{ $loginBg ? asset("images/" . $loginBg) : asset("images/default-bg.jpg") }}')
        no-repeat center center fixed;
        background-size: cover;
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
    {{-- ✅ Logo --}}
    @if ($schoolLogo)
        <img src="{{ asset('images/' . $schoolLogo) }}" alt="Logo" class="logo">
    @else
        <img src="{{ asset('images/logo.png') }}" alt="Default Logo" class="logo">
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

    {{-- ✅ Login form --}}
    <form method="POST" action="{{ route('login') }}" class="text-start">
        @csrf
        <div class="mb-3">
            <label>Email Address</label>
            <input type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="remember" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label">Remember Me</label>
        </div>

        <button class="btn btn-primary w-100">Login</button>
    </form>

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
