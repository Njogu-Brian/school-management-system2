@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Transport / Driver Change Requests</div>
                <h1>Driver Change Requests</h1>
                <p>Review and manage driver change requests.</p>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->user()->hasRole('Driver'))
                    <a href="{{ route('transport.driver-change-requests.create') }}" class="btn btn-settings-primary">
                        <i class="bi bi-plus-circle"></i> Create Request
                    </a>
                @endif
                <a href="{{ url('/transport') }}" class="btn btn-ghost-strong">Back to Transport</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-3">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mt-3">{{ session('error') }}</div>
        @endif

        {{-- Requests Table --}}
        <div class="settings-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Change Requests</h5>
                <span class="input-chip">{{ $requests->total() }} total</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
                                    <th>Driver</th>
                                @endif
                                <th>Current Trip</th>
                                <th>Request Type</th>
                                <th>Requested Change</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $request)
                                <tr>
                                    @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
                                        <td>
                                            @if($request->driver)
                                                <strong>{{ $request->driver->first_name }} {{ $request->driver->last_name }}</strong>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        @if($request->trip)
                                            {{ $request->trip->trip_name ?? $request->trip->name }}
                                            @if($request->trip->vehicle)
                                                <br><small class="text-muted">{{ $request->trip->vehicle->vehicle_number }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ ucfirst(str_replace('_', ' ', $request->request_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($request->request_type === 'reassignment' && $request->requestedTrip)
                                            {{ $request->requestedTrip->trip_name ?? $request->requestedTrip->name }}
                                            @if($request->requestedTrip->vehicle)
                                                <br><small class="text-muted">{{ $request->requestedTrip->vehicle->vehicle_number }}</small>
                                            @endif
                                        @elseif($request->request_type === 'dropoff_change' && $request->requestedDropOffPoint)
                                            {{ $request->requestedDropOffPoint->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ Str::limit($request->reason, 50) }}</small>
                                    </td>
                                    <td>
                                        @if($request->status === 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($request->status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $request->created_at->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        @if($request->status === 'pending' && auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
                                            <form action="{{ route('transport.driver-change-requests.approve', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this request?')">Approve</button>
                                            </form>
                                            <form action="{{ route('transport.driver-change-requests.reject', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">Reject</button>
                                            </form>
                                        @endif
                                        @if($request->review_notes)
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="{{ $request->review_notes }}">
                                                <i class="bi bi-info-circle"></i> Notes
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']) ? '8' : '7' }}" class="text-center text-muted py-4">
                                        No change requests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($requests->hasPages())
                <div class="card-body border-top">
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

