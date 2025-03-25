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

        @if(optional(Auth::user())->role === 'admin')
            <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
            <a href="{{ route('students.index') }}">Manage Students</a>
        @elseif(Auth::user()->role === 'teacher')
            <a href="{{ route('teacher.dashboard') }}">Teacher Dashboard</a>
        @endif

        <a href="{{ route('attendance.mark.form') }}">Mark Attendance</a>
        <a href="{{ route('transport.index') }}">Transport</a>
        <a href="{{ route('notify-kitchen') }}">Notify Kitchen</a>

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
