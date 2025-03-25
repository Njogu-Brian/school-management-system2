@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Teacher Dashboard</h1>

    <div class="row">
        <div class="col-md-12">
            <h3>My Students' Attendance</h3>
            @include('dashboard.partials.summary')
        </div>
    </div>
</div>
@endsection
