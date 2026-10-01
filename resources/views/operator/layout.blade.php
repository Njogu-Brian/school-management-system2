<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduLynk Operator')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --el-primary: #0b3d91; }
        body { background: #f4f6f9; }
        .navbar { background: var(--el-primary); }
        .stat-card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
    </style>
</head>
<body>
<nav class="navbar navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('operator.dashboard') }}">EduLynk Operator</a>
        <div class="d-flex gap-3">
            <a class="nav-link text-white" href="{{ route('operator.dashboard') }}">Dashboard</a>
            <a class="nav-link text-white" href="{{ route('operator.schools.index') }}">Schools</a>
            <a class="nav-link text-white" href="{{ route('operator.billing.index') }}">Billing</a>
            <a class="nav-link text-white-50" href="{{ url('/logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
            <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="d-none">@csrf</form>
        </div>
    </div>
</nav>
<main class="container pb-5">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
