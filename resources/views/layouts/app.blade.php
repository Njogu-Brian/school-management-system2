<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Royal Kings Education Centre</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    @if(request()->is('finance*') || request()->is('voteheads*'))
    <link rel="stylesheet" href="{{ asset('css/finance-modern.css') }}">
    <style>
        :root {
            --finance-primary: {{ \App\Models\Setting::where('key', 'finance_primary_color')->first()?->value ?? '#6366f1' }};
            --finance-secondary: {{ \App\Models\Setting::where('key', 'finance_secondary_color')->first()?->value ?? '#764ba2' }};
            --finance-success: {{ \App\Models\Setting::where('key', 'finance_success_color')->first()?->value ?? '#10b981' }};
            --finance-warning: {{ \App\Models\Setting::where('key', 'finance_warning_color')->first()?->value ?? '#f59e0b' }};
            --finance-danger: {{ \App\Models\Setting::where('key', 'finance_danger_color')->first()?->value ?? '#ef4444' }};
            --finance-info: {{ \App\Models\Setting::where('key', 'finance_info_color')->first()?->value ?? '#06b6d4' }};
        }
        .finance-page {
            font-family: '{{ \App\Models\Setting::where('key', 'finance_primary_font')->first()?->value ?? 'Inter' }}', 'Poppins', sans-serif;
        }
        .finance-header h1,
        .finance-header h2,
        .finance-header h3 {
            font-family: '{{ \App\Models\Setting::where('key', 'finance_heading_font')->first()?->value ?? 'Poppins' }}', sans-serif;
        }
        .finance-gradient-1 {
            background: linear-gradient(135deg, var(--finance-primary) 0%, var(--finance-secondary) 100%);
        }
        .finance-card-header,
        .finance-table thead,
        .finance-stat-card.primary::before {
            background: linear-gradient(135deg, var(--finance-primary) 0%, var(--finance-secondary) 100%);
        }
        .btn-finance-primary {
            background: linear-gradient(135deg, var(--finance-primary) 0%, var(--finance-secondary) 100%);
        }
    </style>
    @endif
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

    <style>
        :root {
            --brand-primary: {{ setting('finance_primary_color', '#3a1a59') }};
            --brand-primary-dark: {{ setting('finance_primary_color', '#2e1344') }};
            --brand-accent: {{ setting('finance_secondary_color', '#14b8a6') }};
            --brand-bg: #f8f9fa;
            --brand-surface: #ffffff;
            --brand-border: #e5e7eb;
            --brand-text: #0f172a;
            --brand-muted: #6b7280;
        }
        body {
            font-family: 'Poppins', sans-serif;
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
            z-index: 1500;
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
        .sidebar a.parent-active {
            background: color-mix(in srgb, var(--brand-primary) 85%, #ffffff 15%);
            color: #fff;
        }
        .collapse a {
            margin-left: 25px;
            font-size: 14px;
            color: #d1c4e9;
        }
        .collapse a.active {
            color: #ffc107;
            font-weight: 600;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
            min-height: 100vh;
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
            z-index: 1400;
            background: color-mix(in srgb, var(--brand-primary) 6%, #ffffff 94%);
            border: 1px solid var(--brand-border);
            border-radius: 14px;
            padding: 10px 14px;
            box-shadow: 0 12px 24px rgba(0,0,0,0.06);
            flex-wrap: wrap;
        }
        .header-alerts {
            margin-left: auto;
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
        }
    </style>
</head>
<body class="@auth with-sidebar @endauth">
    @auth
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle"> <i class="bi bi-list"></i></button>

    <div class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="School Logo">
            <h5>Royal Kings School</h5>
        </div>

 @php
  $u = Auth::user();
  $onTeacherRoute = request()->routeIs('teacher.*') || request()->is('teacher/*');

  // Case-tolerant teacher check
  $isTeacher = $u && (
      $u->hasAnyRole(['Teacher','teacher']) ||
      ($u->roles->pluck('name')->map(fn($n)=>strtolower($n))->contains('teacher'))
  );
@endphp

@if($onTeacherRoute && $isTeacher)
  @include('layouts.partials.nav-teacher')

@elseif($u && $u->hasAnyRole(['Super Admin','Admin','Secretary']))
  @include('layouts.partials.nav-admin')

@elseif($isTeacher)
  @include('layouts.partials.nav-teacher')
@else
  {{-- Optional: safe fallback so the sidebar never appears empty --}}
  @include('layouts.partials.nav-admin')
@endif


        <!-- Logout -->
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
     @endauth

    <div class="content">
        @auth
        <div class="app-header d-flex align-items-center gap-3 mb-3">
            <div class="header-actions ms-auto">
                <div class="dropdown header-alerts">
                    <button class="btn btn-ghost-strong btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i> Alerts
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">No alerts yet</span></li>
                    </ul>
                </div>
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
        <div class="page-wrapper @if(request()->is('finance*') || request()->is('voteheads*')) finance-page @endif">@yield('content')</div>
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
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
