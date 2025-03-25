@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Admin Dashboard</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(optional(Auth::user())->role === 'admin')
        @include('dashboard.partials.filters')

        <div class="row">
            <div class="col-md-6">
                <h3>Students Overview</h3>
                @include('dashboard.partials.students')
            </div>

            <div class="col-md-6">
                <h3>Attendance Summary</h3>
                @include('dashboard.partials.summary')
            </div>
        </div>
    @else
        <p class="text-danger">Unauthorized Access</p>
    @endif
</div>
@endsection
