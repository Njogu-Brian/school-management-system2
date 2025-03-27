<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background: #495057;
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
        <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>

        <!-- Student Information -->
        <a href="#studentMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="studentMenu">
            Student Information
        </a>
        <div class="collapse" id="studentMenu">
            <a href="{{ route('students.create') }}" style="margin-left: 15px;">New Admissions</a>
            <a href="{{ route('students.index') }}" style="margin-left: 15px;">Student Details</a>
            <a href="{{ route('student-categories.index') }}" style="margin-left: 15px;">Student Categories</a>
        </div>

        <!-- Academic Management -->
        <a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="academicsMenu">
            Academics
        </a>
        <div class="collapse" id="academicsMenu">
            <a href="{{ route('classrooms.index') }}" style="margin-left: 15px;">Classrooms</a>
            <a href="{{ route('streams.index') }}" style="margin-left: 15px;">Streams</a>
            <a href="{{ route('student-categories.index') }}" style="margin-left: 15px;">Student Categories</a>
        </div>

        <!-- Parent Management -->
        <a href="#parentMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="parentMenu">
            Parent Management
        </a>
        <div class="collapse" id="parentMenu">
            <a href="{{ route('parent-info.create') }}" style="margin-left: 15px;">Add Parent</a>
            <a href="{{ route('parent-info.index') }}" style="margin-left: 15px;">View Parents</a>
        </div>

        <!-- Staff Management -->
        <a href="{{ route('staff.index') }}">Manage Staff</a>

        <!-- Transport Management -->
        <a href="#transportMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="transportMenu">
            Transport
        </a>
        <div class="collapse" id="transportMenu">
            <a href="{{ route('vehicles.index') }}" style="margin-left: 15px;">Manage Vehicles</a>
            <a href="{{ route('routes.index') }}" style="margin-left: 15px;">Manage Routes</a>
        </div>

        <!-- Kitchen and Attendance -->
        <a href="{{ route('notify-kitchen') }}">Notify Kitchen</a>
        <a href="{{ route('attendance.mark.form') }}">Mark Attendance</a>

    @elseif(Auth::user()->hasRole('teacher'))
        <a href="{{ route('teacher.dashboard') }}">Teacher Dashboard</a>
        <a href="{{ route('attendance.mark.form') }}">Mark Attendance</a>
    @endif

    <!-- Logout -->
    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-danger">Logout</a>
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
