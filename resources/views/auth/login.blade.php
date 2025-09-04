@extends('layouts.app')

@section('content')
@php
    $schoolName = $settings['school_name']->value ?? 'School Management System';
    $schoolLogo = $settings['school_logo']->value ?? null;
    $loginBg = $settings['login_background']->value ?? null;
@endphp

<style>
    body {
        background: url('{{ $loginBg ? asset("storage/" . $loginBg) : asset("default-bg.jpg") }}') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .login-box {
        background: rgba(255,255,255,0.95);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        max-width: 420px;
        width: 100%;
    }
    .login-box img.logo {
        max-height: 70px;
        margin-bottom: 15px;
    }
    .announcements {
        background: #f9f9f9;
        border-left: 4px solid #007bff;
        padding: 10px;
        margin-top: 20px;
        font-size: 14px;
    }
</style>

<div class="login-box text-center">
    @if ($schoolLogo)
        <img src="{{ asset('storage/' . $schoolLogo) }}" alt="Logo" class="logo">
    @endif

    <h5 class="mb-3">{{ $schoolName }}</h5>

    {{-- ✅ Show errors --}}
    @if ($errors->any())
        <div class="alert alert-danger text-start">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ✅ Show success/info messages (optional) --}}
    @if (session('status'))
        <div class="alert alert-success text-start">
            {{ session('status') }}
        </div>
    @endif

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

    <div class="announcements mt-4 text-start">
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
