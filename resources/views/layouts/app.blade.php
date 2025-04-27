<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
            body {
            display: flex;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: #343a40;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            overflow-y: auto; /* 👈 enables vertical scroll */
            scroll-behavior: smooth;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 500;
        }

        .sidebar i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background: #495057;
        }

        .sublink {
            margin-left: 30px;
            font-size: 14px;
            background-color: #212529;
            padding-left: 20px;
            border-left: 3px solid #17a2b8;
        }

        .sublink:hover {
            background-color: #495057;
        }

        .content {
            margin-left: 260px;
            padding: 20px;
            width: 100%;
        }

    </style>
</head>
<body>

   <!-- Sidebar Navigation -->
    @if(Auth::check())
    <div class="sidebar">
        <h3 class="text-center">Navigation</h3>

        @if(Auth::user()->hasRole('admin'))
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>

            <!-- Student Information -->
            <a href="#studentMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="studentMenu">
                <i class="bi bi-person"></i> Student Information
            </a>
            <div class="collapse" id="studentMenu">
                <a href="{{ route('students.create') }}" class="sublink"><i class="bi bi-person-plus"></i> New Admissions</a>
                <a href="{{ route('students.index') }}" class="sublink"><i class="bi bi-people"></i> Student Details</a>
                <a href="{{ route('online-admissions.index') }}" class="sublink"><i class="bi bi-file-earmark-text"></i> Online Admission</a>
                <a href="{{ route('student-categories.index') }}" class="sublink"><i class="bi bi-tags"></i> Student Categories</a>
            </div>

            <!-- Academic Management -->
            <a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="academicsMenu">
                <i class="bi bi-journal-bookmark"></i> Academics
            </a>
            <div class="collapse" id="academicsMenu">
                <a href="{{ route('classrooms.index') }}" class="sublink"><i class="bi bi-house-door"></i> Classrooms</a>
                <a href="{{ route('streams.index') }}" class="sublink"><i class="bi bi-signpost-split"></i> Streams</a>
                <a href="{{ route('student-categories.index') }}" class="sublink"><i class="bi bi-tags"></i> Student Categories</a>
            </div>

            <!-- Staff Management -->
            <a href="{{ route('staff.index') }}"><i class="bi bi-person-badge"></i> Manage Staff</a>

        
            <!-- Transport Management -->
            <a href="#transportMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="transportMenu">
                <i class="bi bi-truck"></i> Transport
            </a>
            <div class="collapse" id="transportMenu">
                <a href="{{ route('vehicles.index') }}" class="sublink"><i class="bi bi-bus-front"></i> Manage Vehicles</a>
                <a href="{{ route('routes.index') }}" class="sublink"><i class="bi bi-map"></i> Manage Routes</a>
                <a href="{{ route('trips.index') }}" class="sublink"><i class="bi bi-geo-alt"></i> Manage Trips</a>
                <a href="{{ route('dropoffpoints.index') }}" class="sublink"><i class="bi bi-geo"></i> Drop-Off Points</a>
                <a href="{{ route('student_assignments.index') }}" class="sublink"><i class="bi bi-people"></i> Student Assignment</a>
            </div>
            
            <!-- Kitchen and Attendance -->
            <a href="{{ route('notify-kitchen') }}"><i class="bi bi-bell"></i> Notify Kitchen</a>
            <a href="{{ route('attendance.mark.form') }}"><i class="bi bi-calendar-check"></i> Mark Attendance</a>
        @endif

        @if(Auth::user()->hasRole('teacher'))
            <a href="{{ route('teacher.dashboard') }}"><i class="bi bi-speedometer2"></i> Teacher Dashboard</a>
            <a href="{{ route('attendance.mark.form') }}"><i class="bi bi-calendar-check"></i> Mark Attendance</a>
        @endif
        <!-- Communication -->
        <a href="#communicationMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="communicationMenu">
            <i class="bi bi-chat-dots"></i> Communication
        </a>
        <div class="collapse" id="communicationMenu">
            <a href="{{ route('communication.send.email') }}" class="sublink"><i class="bi bi-envelope"></i> Send Email</a>
            <a href="{{ route('communication.send.sms') }}" class="sublink"><i class="bi bi-chat-left-dots"></i> Send SMS</a>
            <a href="{{ route('communication.logs') }}" class="sublink"><i class="bi bi-clock-history"></i> Message Logs</a>
            <a href="{{ route('communication.logs.scheduled') }}" class="sublink"><i class="bi bi-calendar-event"></i> Scheduled Logs</a>
            <a href="{{ route('email-templates.index') }}" class="sublink"><i class="bi bi-card-text"></i> Email Templates</a>
            <a href="{{ route('sms-templates.index') }}" class="sublink"><i class="bi bi-sim"></i> SMS Templates</a>
            <a href="{{ route('announcements.index') }}" class="sublink"><i class="bi bi-megaphone"></i> Announcements</a>
        </div>

        <!-- System Settings -->
        <a href="#settingsMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="settingsMenu">
            <i class="bi bi-gear-wide-connected"></i> Settings
        </a>
        <div class="collapse" id="settingsMenu">
            <a href="{{ route('settings.index') }}" class="sublink"><i class="bi bi-building"></i> General Info</a>
            <a href="{{ route('settings.role_permissions') }}" class="sublink"><i class="bi bi-building"></i>Roles</a>
        </div>
        
        <!-- Logout -->
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
    @endif



    <!-- Main Content Area -->
    <div class="content">
        <div class="container mt-4">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
