@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Transport Management</h1>
    <a href="{{ route('vehicles.create') }}" class="btn btn-primary">Add New Vehicle</a>
    <a href="{{ route('routes.create') }}" class="btn btn-success">Add New Route</a>

    <h2>Vehicles</h2>
    <!-- Display vehicle data here -->

    <h2>Routes</h2>
    <!-- Display routes data here -->
</div>
@endsection
