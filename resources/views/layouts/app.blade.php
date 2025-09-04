<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Kings Education Centre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            height: 100vh;
            background: #3a1a59; /* Dark purple */
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 15px;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .sidebar .brand {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar .brand img {
            width: 70px;
            margin-bottom: 8px;
        }

        .sidebar .brand h5 {
            font-size: 14px;
            font-weight: 600;
            color: #f1f1f1;
        }

        /* Main links */
        .sidebar a {
            color: #e6dbf7;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 14px;
            margin: 3px 10px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar a i {
            margin-right: 8px;
            font-size: 16px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #4b256f;
            color: #fff;
        }

        /* Sublinks */
        .sublink {
            margin-left: 28px;
            font-size: 13px;
            font-weight: 500;
            color: #d1c4e9;
            border-left: 3px solid transparent;
            padding: 8px 12px;
            border-radius: 4px;
        }

        .sublink:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .sublink.active {
            border-left: 3px solid #ffc107;
            background-color: rgba(255, 255, 255, 0.15);
            font-weight: 600;
            color: #fff;
        }

        /* Content */
        .content {
            margin-left: 230px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .page-wrapper {
            max-width: 100%;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { left: -230px; }
            .sidebar.active { left: 0; }
            .content { margin-left: 0; }
        }

        @media (max-width: 576px) {
            .sidebar { width: 200px; }
            .content { padding: 15px; }
        }

        /* Toggle button */
        .sidebar-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            background: #3a1a59;
            color: white;
            border: none;
            font-size: 20px;
            border-radius: 4px;
            z-index: 1100;
        }
        .sidebar-toggle:hover {
            background: #4b256f;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    @php
        $isStudentActive = Request::is('students*') || Request::is('online-admissions*') || Request::is('student-categories*');
        $isFinanceActive = Request::is('voteheads*') || Request::is('fee-structures*') || Request::is('invoices*') || Request::is('optional_fees*');
        $isAcademicsActive = Request::is('classrooms*') || Request::is('streams*');
        $isTransportActive = Request::is('vehicles*') || Request::is('routes*') || Request::is('trips*') || Request::is('dropoffpoints*') || Request::is('student_assignments*');
        $isCommunicationActive = Request::is('communication*') || Request::is('email-templates*') || Request::is('sms-templates*') || Request::is('announcements*');
        $isSettingsActive = Request::is('settings*');
        $isAttendanceActive = Request::is('attendance*');
    @endphp

    @auth
    <div class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="School Logo">
            <h5>Royal Kings School</h5>
        </div>

        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" class="{{ Request::is('admin/dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i><span> Dashboard</span>
        </a>

        <!-- Students -->
        <a href="#studentMenu" data-bs-toggle="collapse" aria-expanded="{{ $isStudentActive ? 'true' : 'false' }}">
            <i class="bi bi-person"></i><span> Students</span>
        </a>
        <div class="collapse {{ $isStudentActive ? 'show' : '' }}" id="studentMenu">
            <a href="{{ route('students.index') }}" class="sublink {{ Request::is('students') ? 'active' : '' }}"><i class="bi bi-people"></i> Student Details</a>
            <a href="{{ route('students.create') }}" class="sublink {{ Request::is('students/create') ? 'active' : '' }}"><i class="bi bi-person-plus"></i> Admissions</a>
            <a href="{{ route('students.bulk') }}" class="sublink {{ Request::is('students/bulk') ? 'active' : '' }}"><i class="bi bi-upload"></i> Bulk Upload</a>
            <a href="{{ route('online-admissions.index') }}" class="sublink {{ Request::is('online-admissions*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Online Admission</a>
        </div>

        <!-- Finance -->
        <a href="#financeMenu" data-bs-toggle="collapse" aria-expanded="{{ $isFinanceActive ? 'true' : 'false' }}">
            <i class="bi bi-currency-dollar"></i><span> Finance</span>
        </a>
        <div class="collapse {{ $isFinanceActive ? 'show' : '' }}" id="financeMenu">
            <a href="{{ route('finance.voteheads.index') }}" class="sublink {{ Request::is('voteheads*') ? 'active' : '' }}"><i class="bi bi-list"></i> Voteheads</a>
            <a href="{{ route('finance.fee-structures.manage') }}" class="sublink {{ Request::is('fee-structures*') ? 'active' : '' }}"><i class="bi bi-diagram-3"></i> Fee Structures</a>
            <a href="{{ route('finance.invoices.index') }}" class="sublink {{ Request::is('invoices*') ? 'active' : '' }}"><i class="bi bi-receipt"></i> Invoices</a>
            <a href="{{ route('finance.optional_fees.index') }}" class="sublink {{ Request::is('optional_fees*') ? 'active' : '' }}"><i class="bi bi-toggle2-on"></i> Optional Fees</a>
        </div>

        <!-- Academics -->
        <a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="{{ $isAcademicsActive ? 'true' : 'false' }}">
            <i class="bi bi-journal-bookmark"></i><span> Academics</span>
        </a>
        <div class="collapse {{ $isAcademicsActive ? 'show' : '' }}" id="academicsMenu">
            <a href="{{ route('classrooms.index') }}" class="sublink {{ Request::is('classrooms*') ? 'active' : '' }}"><i class="bi bi-house"></i> Classrooms</a>
            <a href="{{ route('streams.index') }}" class="sublink {{ Request::is('streams*') ? 'active' : '' }}"><i class="bi bi-signpost-split"></i> Streams</a>
        </div>

        <!-- Staff -->
        <a href="{{ route('staff.index') }}" class="{{ Request::is('staff*') ? 'active' : '' }}"><i class="bi bi-person-badge"></i><span> Staff</span></a>

        <!-- Transport -->
        <a href="#transportMenu" data-bs-toggle="collapse" aria-expanded="{{ $isTransportActive ? 'true' : 'false' }}">
            <i class="bi bi-truck"></i><span> Transport</span>
        </a>
        <div class="collapse {{ $isTransportActive ? 'show' : '' }}" id="transportMenu">
            <a href="{{ route('transport.vehicles.index') }}" class="sublink {{ Request::is('vehicles*') ? 'active' : '' }}"><i class="bi bi-bus-front"></i> Vehicles</a>
            <a href="{{ route('transport.routes.index') }}" class="sublink {{ Request::is('routes*') ? 'active' : '' }}"><i class="bi bi-map"></i> Routes</a>
            <a href="{{ route('transport.trips.index') }}" class="sublink {{ Request::is('trips*') ? 'active' : '' }}"><i class="bi bi-geo"></i> Trips</a>
            <a href="{{ route('transport.student-assignments.index') }}" class="sublink {{ Request::is('student_assignments*') ? 'active' : '' }}"><i class="bi bi-people"></i> Assignments</a>
        </div>

        <!-- Attendance -->
        <a href="#attendanceMenu" data-bs-toggle="collapse" aria-expanded="{{ $isAttendanceActive ? 'true' : 'false' }}">
            <i class="bi bi-calendar-check"></i><span> Attendance</span>
        </a>
        <div class="collapse {{ $isAttendanceActive ? 'show' : '' }}" id="attendanceMenu">
            <a href="{{ route('attendance.mark.form') }}" class="sublink {{ Request::is('attendance/mark*') ? 'active' : '' }}"><i class="bi bi-pencil"></i> Mark Attendance</a>
            <a href="{{ route('attendance.records') }}" class="sublink {{ Request::is('attendance/records*') ? 'active' : '' }}"><i class="bi bi-journal-text"></i> Reports</a>
            <a href="{{ route('attendance.notifications.notify.form') }}" class="sublink {{ Request::is('attendance/notifications/notify*') ? 'active' : '' }}"><i class="bi bi-bell"></i> Notify Recipients</a>
            <a href="{{ route('attendance.notifications.index') }}" class="sublink {{ Request::is('attendance/notifications*') && !Request::is('attendance/notifications/notify*') ? 'active' : '' }}"><i class="bi bi-people"></i> Recipients</a>
        </div>

        <!-- Communication -->
        <a href="#communicationMenu" data-bs-toggle="collapse" aria-expanded="{{ $isCommunicationActive ? 'true' : 'false' }}">
            <i class="bi bi-chat-dots"></i><span> Communication</span>
        </a>
        <div class="collapse {{ $isCommunicationActive ? 'show' : '' }}" id="communicationMenu">
            <a href="{{ route('communication.send.email') }}" class="sublink {{ Request::is('communication/send/email*') ? 'active' : '' }}"><i class="bi bi-envelope"></i> Send Email</a>
            <a href="{{ route('communication.send.sms') }}" class="sublink {{ Request::is('communication/send/sms*') ? 'active' : '' }}"><i class="bi bi-chat"></i> Send SMS</a>
            <a href="{{ route('communication.logs') }}" class="sublink {{ Request::is('communication/logs*') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Logs</a>
            <a href="{{ route('announcements.index') }}" class="sublink {{ Request::is('announcements*') ? 'active' : '' }}"><i class="bi bi-megaphone"></i> Announcements</a>
        </div>

        <!-- Settings -->
        <a href="#settingsMenu" data-bs-toggle="collapse" aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}">
            <i class="bi bi-gear"></i><span> Settings</span>
        </a>
        <div class="collapse {{ $isSettingsActive ? 'show' : '' }}" id="settingsMenu">
            <a href="{{ route('settings.index') }}" class="sublink {{ Request::is('settings') ? 'active' : '' }}"><i class="bi bi-building"></i> General Info</a>
            <a href="{{ route('settings.role_permissions') }}" class="sublink {{ Request::is('settings/role_permissions*') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i> Roles & Permissions</a>
        </div>

        <!-- Logout -->
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger">
            <i class="bi bi-box-arrow-right"></i><span> Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
    @endauth

    <!-- Main Content -->
    <div class="content">
        <div class="page-wrapper">
            @yield('content')
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("sidebarToggle").addEventListener("click", function () {
                document.querySelector(".sidebar").classList.toggle("active");
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
