@extends('layouts.app')

@section('content')
<h1>Drop-Off Points Management</h1>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('transport.dropoffpoints.create') }}" class="btn btn-success">Add New Drop-Off Point</a>
    <a href="{{ route('transport.dropoffpoints.import.form') }}" class="btn btn-primary">Import</a>
    <a href="{{ route('transport.dropoffpoints.template') }}" class="btn btn-outline-secondary">Download Template</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <strong>There were some problems with your submission:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<table class="table table-bordered align-middle">
    <thead>
        <tr>
            <th>Name</th>
            <th>Route</th>
            <th>Vehicles</th>
            <th style="width:180px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($dropOffPoints as $point)
            <tr>
                <td>{{ $point->name }}</td>
                <td>{{ $point->route->name ?? 'No Route' }}</td>
                <td>
                    @if(isset($point->vehicles) && $point->vehicles->count())
                        <ul class="mb-0 ps-3">
                            @foreach($point->vehicles as $vehicle)
                                <li>{{ $vehicle->registration_number ?? 'Vehicle #'.$vehicle->id }}</li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-muted">None</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('transport.dropoffpoints.edit', $point->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <form action="{{ route('transport.dropoffpoints.destroy', $point->id) }}"
                          method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this drop-off point?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center text-muted">No Drop-Off Points Found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
