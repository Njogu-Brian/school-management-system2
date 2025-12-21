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

        /* Guided navigation */
        .guided-tour-toggle {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
            color: #fff;
            border: none;
            padding: 8px 12px;
            font-weight: 600;
            box-shadow: 0 10px 18px rgba(0,0,0,0.12);
        }
        .guided-tour-panel {
            position: fixed;
            right: 18px;
            bottom: 24px;
            width: min(440px, 90vw);
            background: linear-gradient(135deg, var(--brand-primary) 0%, color-mix(in srgb, var(--brand-primary) 70%, var(--brand-accent) 30%) 100%);
            color: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.25);
            padding: 16px 18px;
            display: none;
            z-index: 3000;
        }
        .guided-tour-panel.active { display: block; }
        .guided-tour-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .guided-tour-step {
            font-size: 12px;
            opacity: 0.85;
        }
        .guided-tour-list {
            margin: 0;
            padding-left: 1.1rem;
            color: rgba(255,255,255,0.9);
            font-size: 13px;
        }
        .guided-tour-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .guided-tour-actions .btn-light {
            color: var(--brand-primary);
            font-weight: 600;
        }
        .guided-tour-progress {
            width: 6px;
            background: rgba(255,255,255,0.25);
            border-radius: 999px;
            position: relative;
            flex-shrink: 0;
            height: 100%;
            min-height: 120px;
        }
        .guided-tour-progress .bar {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #fff;
            border-radius: 999px;
            transition: height 0.25s ease;
        }
        .guided-tour-close {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.8);
        }

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

@php
    $guidedTourSteps = [
        [
            'title' => 'Dashboard overview',
            'route_name' => 'admin.dashboard',
            'summary' => 'Start here to see KPIs, shortcuts, and quick links for every module.',
            'highlights' => [
                'Live stats for admissions, finance, HR, and teaching.',
                'Tiles link directly into deeper module pages.'
            ],
        ],
        [
            'title' => 'Students & admissions',
            'route_name' => 'students.index',
            'summary' => 'View and manage student records with sibling and online admission links.',
            'highlights' => [
                'Find students fast, open profiles, and jump to medical/discipline tabs.',
                'Admissions: capture new students or bulk-import from spreadsheets.'
            ],
        ],
        [
            'title' => 'Attendance & follow-up',
            'route_name' => 'attendance.mark.form',
            'summary' => 'Record attendance, review trends, and notify guardians about issues.',
            'highlights' => [
                'Mark daily attendance or back-fill missed days.',
                'Check at-risk students and send notifications to recipients.'
            ],
        ],
        [
            'title' => 'Academics setup',
            'route_name' => 'academics.classrooms.index',
            'summary' => 'Build classrooms, streams, subjects, and assign teachers.',
            'highlights' => [
                'Create subject groups and map teachers to classes.',
                'Maintain promotions and timetables for learning continuity.'
            ],
        ],
        [
            'title' => 'Exams & results',
            'route_name' => 'academics.exams.index',
            'summary' => 'Plan exams, enter marks, and publish timetables and result slips.',
            'highlights' => [
                'Set exam types and grades, then collect marks in bulk.',
                'Share results and analytics with teachers and guardians.'
            ],
        ],
        [
            'title' => 'Finance workspace',
            'route_name' => 'finance.dashboard',
            'summary' => 'End-to-end fee setup, posting, invoicing, payments, and reminders.',
            'highlights' => [
                'Configure voteheads and fee structures, then post to active terms.',
                'Track invoices, receipts, concessions, and payment plans.'
            ],
        ],
        [
            'title' => 'HR & staff records',
            'route_name' => 'staff.index',
            'summary' => 'Manage staff profiles, roles, leave, attendance, and approvals.',
            'highlights' => [
                'Assign permissions, control access lookups, and review requests.',
                'Track leave balances, attendance, and documents centrally.'
            ],
        ],
        [
            'title' => 'Payroll processing',
            'route_name' => 'hr.payroll.records.index',
            'summary' => 'Run payroll periods, advances, and deductions with audit trails.',
            'highlights' => [
                'Maintain salary structures and deduction types.',
                'Process advances and generate payroll records per period.'
            ],
        ],
        [
            'title' => 'Communication hub',
            'route_name' => 'communication.send.email',
            'summary' => 'Send announcements by email/SMS and keep templates consistent.',
            'highlights' => [
                'Use saved templates for bulk sends.',
                'Review delivery logs and announcements in one place.'
            ],
        ],
        [
            'title' => 'Transport operations',
            'route_name' => 'transport.dashboard',
            'summary' => 'Oversee routes, vehicles, and student assignments for trips.',
            'highlights' => [
                'Create vehicles and routes, then schedule trips.',
                'Assign students to stops and track utilization.'
            ],
        ],
        [
            'title' => 'Inventory & requirements',
            'route_name' => 'inventory.items.index',
            'summary' => 'Track items, student requirements, and requisitions.',
            'highlights' => [
                'Set requirement templates and collect items from students.',
                'Raise requisitions and monitor fulfillment status.'
            ],
        ],
        [
            'title' => 'Point of Sale',
            'route_name' => 'pos.products.index',
            'summary' => 'Manage school shop products, uniforms, and orders.',
            'highlights' => [
                'Configure products and discounts.',
                'Share public links or record in-person sales.'
            ],
        ],
        [
            'title' => 'System settings',
            'route_name' => 'settings.index',
            'summary' => 'Keep school info, academic terms, backups, and logs in sync.',
            'highlights' => [
                'Update school profile, academic years, and calendars.',
                'Super admins can back up, restore, and audit activity.'
            ],
        ],
    ];

    $guidedTourSteps = array_values(array_filter(array_map(function ($step) {
        if (!\Illuminate\Support\Facades\Route::has($step['route_name'])) {
            return null;
        }
        $step['url'] = route($step['route_name']);
        return $step;
    }, $guidedTourSteps)));
@endphp


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
                @if(!empty($guidedTourSteps))
                <button class="guided-tour-toggle btn btn-sm" type="button" id="guidedTourStart">
                    <i class="bi bi-compass"></i> Guide me
                </button>
                @endif
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
        @if(!empty($guidedTourSteps))
        <div id="guidedTourPanel" class="guided-tour-panel">
            <div class="d-flex gap-3 align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="guided-tour-badge">
                            <i class="bi bi-compass"></i>
                            Guided navigation
                        </span>
                        <span class="guided-tour-step" id="guidedTourPosition"></span>
                    </div>
                    <h6 class="mb-1" id="guidedTourTitle"></h6>
                    <p class="small mb-2" id="guidedTourSummary" style="color: rgba(255,255,255,0.9);"></p>
                    <ul class="guided-tour-list mb-3" id="guidedTourHighlights"></ul>
                    <div class="guided-tour-actions">
                        <a id="guidedTourOpen" class="btn btn-light btn-sm" href="#">
                            <i class="bi bi-box-arrow-up-right"></i> Open this step
                        </a>
                        <button id="guidedTourNext" class="btn btn-outline-light btn-sm" type="button">
                            Next
                        </button>
                        <button id="guidedTourPrev" class="btn btn-outline-light btn-sm" type="button">
                            Back
                        </button>
                        <button id="guidedTourClose" class="guided-tour-close" type="button">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="guided-tour-progress">
                    <div class="bar" id="guidedTourProgressBar" style="height: 0;"></div>
                </div>
            </div>
        </div>
        @endif
        <div class="page-wrapper @if(request()->is('finance*') || request()->is('voteheads*')) finance-page @endif">@yield('content')</div>
    </div>

    <script>
        const guidedTourSteps = @json($guidedTourSteps ?? []);
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

        (function(){
            if (!guidedTourSteps.length) return;
            const panel = document.getElementById('guidedTourPanel');
            const startBtn = document.getElementById('guidedTourStart');
            if (!panel || !startBtn) return;

            const titleEl = document.getElementById('guidedTourTitle');
            const summaryEl = document.getElementById('guidedTourSummary');
            const highlightsEl = document.getElementById('guidedTourHighlights');
            const positionEl = document.getElementById('guidedTourPosition');
            const progressBar = document.getElementById('guidedTourProgressBar');
            const openBtn = document.getElementById('guidedTourOpen');
            const nextBtn = document.getElementById('guidedTourNext');
            const prevBtn = document.getElementById('guidedTourPrev');
            const closeBtn = document.getElementById('guidedTourClose');

            const storageKey = 'guidedTourState';
            let state = { active: false, index: 0 };
            try {
                const stored = JSON.parse(localStorage.getItem(storageKey));
                if (stored && typeof stored.index === 'number') {
                    state = { ...state, ...stored };
                }
            } catch (e) { /* ignore */ }

            const clampIndex = () => {
                if (state.index >= guidedTourSteps.length) state.index = guidedTourSteps.length - 1;
                if (state.index < 0) state.index = 0;
            };

            const persist = () => localStorage.setItem(storageKey, JSON.stringify(state));

            const render = () => {
                clampIndex();
                const step = guidedTourSteps[state.index];
                if (!step) return;
                positionEl.textContent = `${state.index + 1} of ${guidedTourSteps.length}`;
                titleEl.textContent = step.title;
                summaryEl.textContent = step.summary;
                highlightsEl.innerHTML = '';
                (step.highlights || []).forEach(text => {
                    const li = document.createElement('li');
                    li.textContent = text;
                    highlightsEl.appendChild(li);
                });
                if (progressBar) {
                    const pct = ((state.index + 1) / guidedTourSteps.length) * 100;
                    progressBar.style.height = `${pct}%`;
                }
                if (openBtn && step.url) {
                    openBtn.href = step.url;
                }
                prevBtn.disabled = state.index === 0;
                nextBtn.textContent = state.index === guidedTourSteps.length - 1 ? 'Finish' : 'Next';
            };

            const openPanel = () => {
                panel.classList.add('active');
                state.active = true;
                persist();
                render();
            };
            const closePanel = () => {
                panel.classList.remove('active');
                state.active = false;
                persist();
            };

            startBtn.addEventListener('click', () => {
                if (panel.classList.contains('active')) {
                    closePanel();
                } else {
                    openPanel();
                }
            });

            nextBtn?.addEventListener('click', () => {
                if (state.index < guidedTourSteps.length - 1) {
                    state.index += 1;
                    persist();
                    render();
                    window.location.href = guidedTourSteps[state.index].url;
                } else {
                    closePanel();
                }
            });

            prevBtn?.addEventListener('click', () => {
                state.index = Math.max(0, state.index - 1);
                persist();
                render();
                window.location.href = guidedTourSteps[state.index].url;
            });

            openBtn?.addEventListener('click', (e) => {
                if (!guidedTourSteps[state.index]?.url) return;
                persist();
                // allow anchor navigation but remember state
            });

            closeBtn?.addEventListener('click', closePanel);
            document.addEventListener('keyup', (e) => {
                if (e.key === 'Escape' && panel.classList.contains('active')) closePanel();
            });

            if (state.active) {
                openPanel();
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
