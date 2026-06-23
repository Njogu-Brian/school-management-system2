<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $schoolNameSetting = \App\Models\Setting::where('key', 'school_name')->first();
        $schoolLogoSetting = \App\Models\Setting::where('key', 'school_logo')->first();
        $faviconSetting = \App\Models\Setting::where('key', 'favicon')->first();

        $appName = $schoolNameSetting?->value ?? config('app.name', 'School Management System');

        $logoSetting = $schoolLogoSetting?->value;
        // If favicon not set, fall back to the uploaded school logo so the icon stays in sync
        $faviconSettingValue = $faviconSetting?->value ?? $logoSetting;

        // Use public_images_path / public_image_url so ASSET_URL works when public files are on another domain
        $logoUrl = null;
        if ($logoSetting && file_exists(public_images_path($logoSetting))) {
            $logoUrl = public_image_url($logoSetting);
        } elseif ($logoSetting && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoSetting)) {
            $logoUrl = \Illuminate\Support\Facades\Storage::url($logoSetting);
        } else {
            $logoUrl = public_image_url('logo.png');
        }

        $faviconUrl = null;
        if ($faviconSettingValue && file_exists(public_images_path($faviconSettingValue))) {
            $faviconUrl = public_image_url($faviconSettingValue);
        } elseif ($faviconSettingValue && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconSettingValue)) {
            $faviconUrl = \Illuminate\Support\Facades\Storage::url($faviconSettingValue);
        } elseif ($logoSetting && file_exists(public_images_path($logoSetting))) {
            $faviconUrl = public_image_url($logoSetting);
        } else {
            $faviconUrl = public_image_url('logo.png');
        }
    @endphp

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $appName }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="{{ $faviconUrl }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    {{-- Static CSS (no Vite build required on server) --}}
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}">
    <style>
        /* Finance legacy class fallbacks aligned to the standard app theme */
        .finance-table { width: 100%; border-collapse: collapse; }
        .finance-table th, .finance-table td { padding: 0.75rem; border-bottom: 1px solid #dee2e6; }
        .finance-table thead th { background: #f8f9fa; }
        .finance-table-wrapper { background: #fff; border: 1px solid #e9ecef; border-radius: 0.5rem; }
        .finance-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 999px; font-size: 0.8rem; }
        .badge-approved { background: #d1e7dd; color: #0f5132; }
        .badge-pending { background: #e2e3e5; color: #41464b; }
        .finance-empty-state { text-align: center; padding: 1.5rem; }
        .finance-empty-state .finance-empty-state-icon { font-size: 2rem; margin-bottom: 0.5rem; color: #6c757d; }
        .finance-action-buttons { display: flex; gap: 0.35rem; flex-wrap: wrap; }
    </style>
    @if(request()->is('communication*'))
    <link rel="stylesheet" href="{{ asset('css/communication-modern.css') }}">
    <style>
        :root {
            --comm-primary: {{ \App\Models\Setting::where('key', 'finance_primary_color')->first()?->value ?? '#6366f1' }};
            --comm-secondary: {{ \App\Models\Setting::where('key', 'finance_secondary_color')->first()?->value ?? '#764ba2' }};
            --comm-success: {{ \App\Models\Setting::where('key', 'finance_success_color')->first()?->value ?? '#10b981' }};
            --comm-warning: {{ \App\Models\Setting::where('key', 'finance_warning_color')->first()?->value ?? '#f59e0b' }};
            --comm-danger: {{ \App\Models\Setting::where('key', 'finance_danger_color')->first()?->value ?? '#ef4444' }};
            --comm-info: {{ \App\Models\Setting::where('key', 'finance_info_color')->first()?->value ?? '#06b6d4' }};
        }
        .comm-page {
            font-family: '{{ \App\Models\Setting::where('key', 'finance_primary_font')->first()?->value ?? 'Inter' }}', 'Poppins', sans-serif;
        }
        .comm-header h1,
        .comm-header h2,
        .comm-header h3 {
            font-family: '{{ \App\Models\Setting::where('key', 'finance_heading_font')->first()?->value ?? 'Poppins' }}', sans-serif;
        }
        .comm-gradient-1 {
            background: linear-gradient(135deg, var(--comm-primary) 0%, var(--comm-secondary) 100%);
        }
        .comm-card-header,
        .comm-table thead {
            background: linear-gradient(135deg, var(--comm-primary) 0%, var(--comm-secondary) 100%);
        }
        .btn-comm-primary {
            background: linear-gradient(135deg, var(--comm-primary) 0%, var(--comm-secondary) 100%);
        }
    </style>
    @endif
    @stack('styles')
    @if(request()->is('finance*') || request()->is('voteheads*'))
        @include('finance.partials.styles')
    @endif

    <style>
        :root {
            --brand-primary: {{ setting('finance_primary_color', '#3a1a59') }};
            --brand-primary-dark: {{ setting('finance_primary_color', '#2e1344') }};
            --brand-accent: {{ setting('finance_secondary_color', '#14b8a6') }};
            --brand-bg: #f8f9fa;
            --brand-surface: {{ setting('finance_surface_color', '#ffffff') }};
            --brand-border: {{ setting('finance_border_color', '#e5e7eb') }};
            --brand-text: {{ setting('finance_text_color', '#0f172a') }};
            --brand-muted: {{ setting('finance_muted_color', '#6b7280') }};
            --nav-highlight: {{ setting('navigation_highlight_color', '#ffc107') }};
        }
        body {
            font-family: '{{ setting('finance_primary_font', 'Poppins') }}', sans-serif;
            background-color: var(--brand-bg);
            color: var(--brand-text);
        }
        body.theme-dark {
            --brand-bg: #0b1220;
            --brand-surface: #111827;
            --brand-border: #1f2937;
            --brand-text: #e5e7eb;
            --brand-muted: #9ca3af;
        }
        /* Readable text on phones; avoid iOS shrinking body copy */
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: var(--brand-primary);
            color: white;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 900;
        }
        .sidebar .brand {
            text-align: center;
            margin-bottom: 25px;
        }
        .sidebar .brand img {
            width: 70px;
            margin-bottom: 8px;
        }
        .sidebar .brand h5 {
            font-size: 15px;
            font-weight: 600;
            color: #f1f1f1;
        }
        .sidebar a {
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .sidebar a:hover,
        .sidebar a.active,
        .sidebar a.parent-active,
        .sidebar a.active-click {
            background: var(--nav-highlight);
            color: #0f172a;
            font-weight: 700;
        }
        .collapse a {
            margin-left: 25px;
            padding: 8px 14px;
            font-size: 14px;
            color: #f4f4f5;
            border-radius: 6px;
        }
        .collapse a.active,
        .collapse a.active-click {
            color: #0f172a;
            font-weight: 700;
            background: var(--nav-highlight);
        }
        .collapse a:focus-visible {
            outline: 2px solid var(--nav-highlight);
            outline-offset: 2px;
        }
        .collapse a.text-muted,
        .collapse a.small {
            color: #e5e7eb !important;
        }
        .sidebar .text-muted,
        .sidebar .text-muted small,
        .sidebar .small,
        .sidebar small {
            color: #e5e7eb !important;
            opacity: 1 !important;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
            min-height: 100vh;
        }
        /* Remove padding for finance pages - they handle their own spacing */
        .content.finance-content {
            padding: 0;
            margin-left: 240px; /* Keep sidebar margin */
        }
        .page-wrapper {
            margin-left: 0;
            width: 100%;
            position: relative;
        }
        /* Ensure finance pages don't have positioning issues */
        .content.finance-content .page-wrapper {
            margin: 0;
            width: 100%;
        }
        .sidebar-toggle {
            position: fixed;
            top: 15px; left: 15px;
            background: var(--brand-primary);
            color: white;
            border: none;
            font-size: 20px;
            border-radius: 4px;
            z-index: 2000;
        }
        .app-header {
            position: sticky;
            top: 0;
            z-index: 950; /* below modal layers */
            background: color-mix(in srgb, var(--brand-primary) 6%, #ffffff 94%);
            border: 1px solid var(--brand-border);
            border-radius: 14px;
            padding: 10px 14px;
            box-shadow: 0 12px 24px rgba(0,0,0,0.06);
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        /* App header for finance pages needs padding since content has none */
        .content.finance-content .app-header {
            margin: 0 20px 20px 20px;
        }
        /* Ensure modals/backdrops sit above the header/sidebar */
        .modal-backdrop { z-index: 2050 !important; }
        .modal { z-index: 2060 !important; }
        .header-alerts {
            margin-left: auto;
            position: relative;
        }
        .header-alerts .alert-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #dc3545;
            color: #fff;
            font-size: 11px;
            line-height: 18px;
            text-align: center;
            font-weight: 700;
        }
        .header-alerts .dropdown-menu {
            width: min(420px, 92vw);
            max-height: 420px;
            overflow-y: auto;
        }
        .header-alerts .alert-item {
            white-space: normal;
            padding: 10px 12px;
        }
        .header-alerts .alert-item .alert-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .header-alerts .alert-item .alert-body {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .header-alerts .alert-item.critical {
            border-left: 3px solid #dc3545;
        }
        .header-alerts .alert-item.warning {
            border-left: 3px solid #ffc107;
        }
        .header-sms-balance .dropdown-menu {
            width: min(320px, 92vw);
        }
        .sms-balance-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .sms-balance-meta {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .system-alert-banner {
            position: sticky;
            top: 0;
            z-index: 1040;
        }
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
        }
        .header-profile .avatar-36 {
            border: 1px solid var(--brand-border);
            background: #fff;
        }
        .profile-dropdown {
            position: relative;
        }
        .profile-dropdown-menu {
            position: absolute;
            right: 0;
            top: 110%;
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.12);
            min-width: 200px;
            padding: 8px;
            display: none;
        }
        .profile-dropdown-menu a {
            display: block;
            padding: 8px 10px;
            border-radius: 8px;
            color: var(--brand-text);
            text-decoration: none;
        }
        .profile-dropdown-menu a:hover {
            background: color-mix(in srgb, var(--brand-primary) 8%, #ffffff 92%);
        }
        .toggle-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }
        .toggle-pill input {
            display: none;
        }
        .toggle-pill .track {
            position: relative;
            width: 74px;
            height: 34px;
            border-radius: 999px;
            background: linear-gradient(135deg, #f3f4f6, #dfe3ea);
            border: 1px solid var(--brand-border);
            transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8px;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.08);
        }
        .toggle-pill .icon {
            font-size: 14px;
            color: var(--brand-muted);
        }
        .toggle-pill .thumb {
            position: absolute;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #ffffff;
            top: 1px;
            left: 1px;
            box-shadow: 0 6px 14px rgba(0,0,0,0.2);
            transition: transform 0.2s ease, background 0.2s ease;
        }
        .toggle-pill input:checked + .track {
            background: linear-gradient(135deg, #0f172a, #1f2937);
            border-color: #0f172a;
            box-shadow: inset 0 2px 6px rgba(0,0,0,0.25);
        }
        .toggle-pill input:checked + .track .icon {
            color: #f8fafc;
        }
        .toggle-pill input:checked + .track .thumb {
            transform: translateX(40px);
            background: #f8fafc;
        }
        .toggle-pill .label {
            font-size: 12px;
            font-weight: 600;
            color: var(--brand-text);
            white-space: nowrap;
        }
        body.theme-dark .brand-toggle {
            background: var(--brand-surface);
            border-color: var(--brand-border);
        }

        .avatar-36 { width:36px; height:36px; border-radius:50%; object-fit:cover; display:inline-block; }
        .avatar-44 { width:44px; height:44px; border-radius:50%; object-fit:cover; display:inline-block; }

        @media(max-width:992px){
            .sidebar{ left:-240px; }
            .sidebar.active{ left:0; }
            .content{ margin-left:0; }
            .content.finance-content { 
                padding: 0;
                margin-left: 0; /* Remove margin on mobile */
            }
            /* ~18px root: larger UI without requiring pinch-zoom */
            html { font-size: 112.5%; }
            .content { padding: 16px 14px; }
            .app-header {
                padding: 12px 14px;
                margin-bottom: 16px;
            }
            .table { font-size: 1rem; }
            .table-sm td, .table-sm th {
                padding: 0.55rem 0.5rem;
                font-size: 0.95rem;
            }
            /* iOS: font-size < 16px on inputs triggers zoom-on-focus */
            .form-control,
            .form-select,
            textarea.form-control,
            .input-group-text {
                font-size: 1rem !important;
                min-height: 2.75rem;
            }
            textarea.form-control { min-height: 6rem; }
            .btn {
                min-height: 2.75rem;
                padding: 0.5rem 1rem;
            }
            .btn-sm {
                min-height: 2.5rem;
                padding: 0.4rem 0.75rem;
                font-size: 0.95rem;
            }
            /* Keep “small” copy legible */
            small, .small {
                font-size: 0.9375rem !important;
            }
        }
    </style>
</head>
<body class="@auth with-sidebar @endauth">
    @auth
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle"> <i class="bi bi-list"></i></button>

    <div class="sidebar">
        <div class="brand">
            <img src="{{ $logoUrl }}" alt="{{ $appName }} Logo">
            <h5>{{ $appName }}</h5>
        </div>

@include(\App\Support\NavAccess::resolvePartial())


        <!-- Logout -->
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
     @endauth

    @php $isFinance = request()->is('finance*') || request()->is('voteheads*'); @endphp
    <div class="content @if($isFinance) finance-content @endif">
        @auth
        @php $isSuperAdmin = auth()->user()->hasRole('Super Admin'); @endphp
        @php $canViewSystemAlerts = auth()->user()->hasAnyRole(['Super Admin', 'Secretary']); @endphp
        <div class="app-header d-flex align-items-center gap-3 mb-3">
            <div class="header-actions ms-auto">
                @if($canViewSystemAlerts)
                <div class="dropdown header-alerts" id="headerAlertsRoot">
                    <button class="btn btn-ghost-strong btn-sm dropdown-toggle position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="headerAlertsToggle">
                        <i class="bi bi-bell"></i> Alerts
                        <span class="alert-badge d-none" id="headerAlertsBadge">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" id="headerAlertsMenu">
                        <li><span class="dropdown-item-text text-muted small">Loading alerts…</span></li>
                    </ul>
                </div>
                <button type="button" class="btn btn-ghost-strong btn-sm d-none" id="enableDesktopNotificationsBtn" title="Enable desktop notifications">
                    <i class="bi bi-app-indicator"></i>
                </button>
                @endif
                @if($isSuperAdmin)
                <div class="dropdown header-sms-balance" id="headerSmsBalanceRoot">
                    <button class="btn btn-ghost-strong btn-sm dropdown-toggle position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="headerSmsBalanceToggle">
                        <i class="bi bi-chat-dots"></i> SMS
                        <span class="alert-badge d-none" id="headerSmsBalanceBadge">!</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-3" id="headerSmsBalanceMenu">
                        <div class="sms-balance-value" id="headerSmsBalanceValue">—</div>
                        <div class="sms-balance-meta mt-1" id="headerSmsBalanceMeta">Checking balance…</div>
                        <div class="d-flex gap-2 mt-3 flex-wrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ url('/communication/queues') }}">Queues</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="headerSmsBalanceRefresh">Refresh</button>
                        </div>
                    </div>
                </div>
                @endif
                <label class="toggle-pill">
                    <input type="checkbox" id="darkModeToggle">
                    <span class="track">
                        <i class="bi bi-sun icon"></i>
                        <i class="bi bi-moon-stars icon"></i>
                        <span class="thumb"></span>
                    </span>
                    <span class="label">Dark</span>
                </label>
                <div class="profile-dropdown">
                    <div class="header-profile cursor-pointer" id="headerProfileToggle">
                        <div class="avatar-36">
                            <img src="{{ auth()->user()->photo_url ?? asset('images/logo.png') }}" alt="Profile" class="avatar-36">
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold">{{ auth()->user()->name ?? 'User' }}</span>
                        </div>
                    </div>
                    @php
                        $profileUrl = \Illuminate\Support\Facades\Route::has('profile.show')
                            ? route('profile.show')
                            : (\Illuminate\Support\Facades\Route::has('profile')
                                ? route('profile')
                                : '/my/profile');
                        $passwordUrl = \Illuminate\Support\Facades\Route::has('password.change')
                            ? route('password.change')
                            : (\Illuminate\Support\Facades\Route::has('password.request')
                                ? route('password.request')
                                : '/password/reset');
                    @endphp
                    <div class="profile-dropdown-menu" id="headerProfileMenu">
                        <a href="{{ $profileUrl }}">Profile</a>
                        <a href="{{ $passwordUrl }}">Change Password</a>
                        <a href="#" class="text-danger" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
                    </div>
                </div>
            </div>
        </div>
        @endauth
        <div id="systemAlertBannerHost"></div>
        <div class="page-wrapper">
            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif
            @if($isFinance)
                <div class="finance-page">
                    <div class="finance-shell">
                        @yield('content')
                    </div>
                </div>
            @else
                @yield('content')
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggles = document.querySelectorAll("#sidebarToggle, .sidebar-toggle");
            const sidebar = document.querySelector(".sidebar");
            if (sidebar && toggles.length) {
                toggles.forEach(btn => {
                    btn.addEventListener("click", function (e) {
                        e.preventDefault();
                        sidebar.classList.toggle("active");
                        document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
                    });
                });
            }
        });
        (function(){
            const body = document.body;
            const darkToggle = document.getElementById('darkModeToggle');

            const themeStored = localStorage.getItem('themeMode') || 'light';

            if (themeStored === 'dark') {
                body.classList.add('theme-dark');
                if (darkToggle) darkToggle.checked = true;
            }

            if (darkToggle) {
                darkToggle.addEventListener('change', () => {
                    const isDark = darkToggle.checked;
                    body.classList.toggle('theme-dark', isDark);
                    localStorage.setItem('themeMode', isDark ? 'dark' : 'light');
                });
            }
            const profileToggle = document.getElementById('headerProfileToggle');
            const profileMenu = document.getElementById('headerProfileMenu');
            if (profileToggle && profileMenu) {
                profileToggle.addEventListener('click', () => {
                    const isOpen = profileMenu.style.display === 'block';
                    profileMenu.style.display = isOpen ? 'none' : 'block';
                });
                document.addEventListener('click', (e) => {
                    if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                        profileMenu.style.display = 'none';
                    }
                });
            }

            @if($canViewSystemAlerts ?? false)
            (function initSystemAlerts() {
                const menu = document.getElementById('headerAlertsMenu');
                const badge = document.getElementById('headerAlertsBadge');
                const bannerHost = document.getElementById('systemAlertBannerHost');
                const smsValue = document.getElementById('headerSmsBalanceValue');
                const smsMeta = document.getElementById('headerSmsBalanceMeta');
                const smsBadge = document.getElementById('headerSmsBalanceBadge');
                const smsRefresh = document.getElementById('headerSmsBalanceRefresh');
                const desktopBtn = document.getElementById('enableDesktopNotificationsBtn');
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const pollMs = 30000;
                let knownIds = new Set(JSON.parse(sessionStorage.getItem('seenSystemAlertIds') || '[]'));
                let audioCtx = null;

                function initDesktopNotifications() {
                    if (!('Notification' in window) || !desktopBtn) return;
                    if (Notification.permission === 'granted') {
                        desktopBtn.classList.add('d-none');
                        return;
                    }
                    desktopBtn.classList.remove('d-none');
                    desktopBtn.addEventListener('click', async () => {
                        const result = await Notification.requestPermission();
                        if (result === 'granted') {
                            desktopBtn.classList.add('d-none');
                            new Notification('ERP alerts enabled', {
                                body: 'You will receive desktop notifications for critical system alerts.',
                            });
                        }
                    });
                }

                function showDesktopNotification(alert) {
                    if (!('Notification' in window) || Notification.permission !== 'granted') return;
                    try {
                        new Notification(alert.title, {
                            body: alert.body,
                            tag: alert.id,
                            requireInteraction: alert.severity === 'critical',
                        });
                    } catch (e) {
                        /* ignore */
                    }
                }

                function playAlertSound() {
                    try {
                        audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.value = 880;
                        gain.gain.value = 0.08;
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start();
                        setTimeout(() => {
                            osc.stop();
                            osc.disconnect();
                            gain.disconnect();
                        }, 280);
                    } catch (e) {
                        /* ignore autoplay restrictions */
                    }
                }

                function escapeHtml(value) {
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                }

                function renderBanner(alerts) {
                    if (!bannerHost) return;
                    bannerHost.innerHTML = '';
                    const critical = alerts.filter(a => a.severity === 'critical');
                    if (!critical.length) return;

                    critical.slice(0, 2).forEach(alert => {
                        const wrap = document.createElement('div');
                        wrap.className = 'alert alert-danger alert-dismissible fade show system-alert-banner mx-3 mt-3 mb-0';
                        wrap.innerHTML = `
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>
                            <strong>${escapeHtml(alert.title)}</strong> — ${escapeHtml(alert.body)}
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                ${alert.deep_link ? `<a class="btn btn-sm btn-light" href="${escapeHtml(alert.deep_link)}">Open</a>` : ''}
                                <button type="button" class="btn btn-sm btn-outline-light" data-alert-id="${escapeHtml(alert.id)}">Mark done</button>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        wrap.querySelector('[data-alert-id]')?.addEventListener('click', () => acknowledgeAlert(alert.id));
                        bannerHost.appendChild(wrap);
                    });
                }

                function renderMenu(alerts) {
                    if (!menu) return;
                    menu.innerHTML = '';
                    if (!alerts.length) {
                        menu.innerHTML = '<li><span class="dropdown-item-text text-muted small">No pending alerts</span></li>';
                    } else {
                        alerts.forEach(alert => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <div class="dropdown-item alert-item ${escapeHtml(alert.severity)}">
                                    <div class="alert-title">${escapeHtml(alert.title)}</div>
                                    <div class="alert-body">${escapeHtml(alert.body)}</div>
                                    <div class="d-flex gap-2 mt-2">
                                        ${alert.deep_link ? `<a class="btn btn-sm btn-outline-primary" href="${escapeHtml(alert.deep_link)}">Open</a>` : ''}
                                        <button type="button" class="btn btn-sm btn-success" data-alert-id="${escapeHtml(alert.id)}">
                                            <i class="bi bi-check2"></i> Done
                                        </button>
                                    </div>
                                </div>
                            `;
                            li.querySelector('[data-alert-id]')?.addEventListener('click', (e) => {
                                e.preventDefault();
                                acknowledgeAlert(alert.id);
                            });
                            menu.appendChild(li);
                        });
                    }

                    const footer = document.createElement('li');
                    footer.innerHTML = `<hr class="dropdown-divider"><a class="dropdown-item small" href="{{ route('activity-logs.index', ['alert_audit' => 1]) }}">View alert audit trail</a>`;
                    menu.appendChild(footer);
                }

                function renderSmsBalance(data) {
                    if (!smsValue || !smsMeta || !smsBadge) return;
                    const balance = data?.balance;
                    const threshold = data?.threshold ?? 50;
                    smsValue.textContent = balance === null || balance === undefined ? 'Unavailable' : `${Number(balance).toLocaleString()} credits`;
                    const parts = [`Threshold: ${Number(threshold).toLocaleString()}`];
                    if (data?.checked_at) parts.push(`Updated ${new Date(data.checked_at).toLocaleString()}`);
                    if (data?.is_paused) parts.push('Communications paused');
                    else if (data?.is_empty) parts.push('Credits exhausted');
                    else if (data?.is_low) parts.push('Low balance');
                    else parts.push('Healthy');
                    smsMeta.textContent = parts.join(' · ');
                    const warn = data?.is_paused || data?.is_empty || data?.is_low;
                    smsBadge.classList.toggle('d-none', !warn);
                }

                async function refreshSmsBalance(force) {
                    const url = `{{ route('admin.alerts.sms-balance') }}${force ? '?refresh=1' : ''}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const payload = await res.json();
                    renderSmsBalance(payload?.data || {});
                }

                function updateBadge(count) {
                    if (!badge) return;
                    if (count > 0) {
                        badge.textContent = String(count);
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                }

                async function acknowledgeAlert(id) {
                    await fetch(`{{ url('/admin/alerts') }}/${id}/acknowledge`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                    });
                    await refreshAlerts(false);
                }

                async function refreshAlerts(playOnNew) {
                    const res = await fetch(`{{ route('admin.alerts.index') }}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!res.ok) return;
                    const payload = await res.json();
                    const alerts = payload?.data?.alerts || [];
                    const count = payload?.data?.pending_count || 0;
                    updateBadge(count);
                    renderMenu(alerts);
                    renderBanner(alerts);

                    const newCritical = alerts.filter(a => a.severity === 'critical' && !knownIds.has(a.id));
                    if (playOnNew && newCritical.length) {
                        playAlertSound();
                        newCritical.forEach(showDesktopNotification);
                    }
                    alerts.forEach(a => knownIds.add(a.id));
                    sessionStorage.setItem('seenSystemAlertIds', JSON.stringify(Array.from(knownIds).slice(-100)));
                }

                initDesktopNotifications();
                if ('Notification' in window && Notification.permission === 'default') {
                    setTimeout(() => Notification.requestPermission().catch(() => {}), 1500);
                }
                refreshAlerts(true);
                refreshSmsBalance(false);
                if (smsRefresh) smsRefresh.addEventListener('click', () => refreshSmsBalance(true));
                setInterval(() => {
                    refreshAlerts(true);
                    refreshSmsBalance(false);
                }, pollMs);
            })();
            @endif
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const initSidebarFocus = function () {
            const sidebar = document.querySelector('.sidebar');
            if (!sidebar) return;

            const clearActiveClicks = () => {
                sidebar.querySelectorAll('a.active-click').forEach(a => a.classList.remove('active-click'));
            };

            const scrollToCenter = (el) => {
                if (el && typeof el.scrollIntoView === 'function') {
                    el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            };

            const ensureParentOpen = (el) => {
                const parentCollapse = el?.closest?.('.collapse');
                if (parentCollapse && typeof bootstrap !== 'undefined') {
                    const instance = bootstrap.Collapse.getOrCreateInstance(parentCollapse, { toggle: false });
                    instance.show();
                }
            };

            const markAndCenter = (el) => {
                if (!el) return;
                clearActiveClicks();
                el.classList.add('active-click');
                ensureParentOpen(el);
                scrollToCenter(el);
            };

            const initialActive = sidebar.querySelector('a.active') || sidebar.querySelector('a.parent-active');
            if (initialActive) {
                clearActiveClicks();
                ensureParentOpen(initialActive);
                scrollToCenter(initialActive);
            }

            sidebar.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', () => markAndCenter(link));
            });

            sidebar.querySelectorAll('[data-bs-toggle="collapse"]').forEach(trigger => {
                const targetSelector = trigger.getAttribute('href');
                if (!targetSelector) return;
                const targetEl = document.querySelector(targetSelector);
                if (!targetEl) return;
                
                // Check if any child link is active (for swimming menu)
                const isSwimmingMenu = targetSelector === '#swimmingMenu';
                const hasActiveChild = targetEl.querySelector('.active') !== null;
                const isOnSwimmingPage = window.location.pathname.includes('/swimming');
                
                // If menu should be open (has 'show' class or has active child), ensure it stays open
                if (targetEl.classList.contains('show') || (isSwimmingMenu && isOnSwimmingPage) || hasActiveChild) {
                    targetEl.classList.add('show');
                    trigger.setAttribute('aria-expanded', 'true');
                    trigger.classList.add('parent-active');
                }
                
                targetEl.addEventListener('shown.bs.collapse', () => trigger.classList.add('parent-active'));
                targetEl.addEventListener('hidden.bs.collapse', () => {
                    // Don't close if we're on a swimming page or has active child
                    if ((isSwimmingMenu && isOnSwimmingPage) || targetEl.querySelector('.active')) {
                        targetEl.classList.add('show');
                        trigger.setAttribute('aria-expanded', 'true');
                        trigger.classList.add('parent-active');
                    } else {
                        trigger.classList.remove('parent-active');
                    }
                });
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebarFocus);
        } else {
            initSidebarFocus();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js" crossorigin="anonymous"></script>
    @include('partials.academic_year_term_filter_script')
    @stack('scripts')
</body>
</html>
