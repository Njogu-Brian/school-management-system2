<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Kings Education Centre</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #3a1a59;
            color: white;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
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
            background: #5a2c8a;
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
            background: #3a1a59;
            color: white;
            border: none;
            font-size: 20px;
            border-radius: 4px;
            z-index: 1100;
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
        <div class="page-wrapper">@yield('content')</div>
    </div>

    <script>
        document.getElementById("sidebarToggle").addEventListener("click", function(){
            document.querySelector(".sidebar").classList.toggle("active");
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
