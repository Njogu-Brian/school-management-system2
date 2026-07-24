@extends('layouts.app')

@section('content')
<h1>Drop-Off Points &amp; Transport Rates</h1>
<p class="text-muted">Set two-way and one-way term fares per drop-off point. Student list prices are calculated from morning/evening legs.</p>

<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="{{ route('transport.dropoffpoints.create') }}" class="btn btn-success">Add New Drop-Off Point</a>
    <a href="{{ route('transport.dropoffpoints.import.form') }}" class="btn btn-primary">Import</a>
    <a href="{{ route('transport.dropoffpoints.template') }}" class="btn btn-outline-secondary">Download Template</a>
    <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-outline-primary">Transport Fees</a>
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
            <th class="text-end">Two-way (KES/term)</th>
            <th class="text-end">One-way (KES/term)</th>
            <th>Students using</th>
            <th>Vehicles</th>
            <th style="width:180px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($dropOffPoints as $point)
            <tr>
                <td>
                    {{ $point->name }}
                    @if($point->isOwnMeans())
                        <span class="badge bg-secondary">System</span>
                    @endif
                </td>
                <td class="text-end">
                    {{ $point->two_way_amount !== null ? number_format($point->two_way_amount, 2) : '—' }}
                </td>
                <td class="text-end">
                    {{ $point->one_way_amount !== null ? number_format($point->one_way_amount, 2) : '—' }}
                </td>
                <td>
                    {{ max((int) $point->morning_assignments_count, (int) $point->evening_assignments_count) }}
                    <small class="text-muted">(M {{ $point->morning_assignments_count }} / E {{ $point->evening_assignments_count }})</small>
                </td>
                <td>
                    @if(isset($point->vehicles) && $point->vehicles->count())
                        <ul class="mb-0 ps-3">
                            @foreach($point->vehicles as $vehicle)
                                <li>{{ $vehicle->registration_number ?? $vehicle->vehicle_number ?? 'Vehicle #'.$vehicle->id }}</li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-muted">None</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('transport.dropoffpoints.edit', $point->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    @unless($point->isOwnMeans())
                    <form action="{{ route('transport.dropoffpoints.destroy', $point->id) }}"
                          method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this drop-off point?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted">No Drop-Off Points Found</td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
