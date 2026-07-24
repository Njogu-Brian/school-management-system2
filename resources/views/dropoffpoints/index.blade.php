@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Drop-off Points &amp; Rates</h1>
                <p class="text-muted mb-0">
                    Set two-way and one-way term fares. Student list prices use morning pickup + evening drop-off only.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.dropoffpoints.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-lg"></i> Add point
                </a>
                <a href="{{ route('transport.dropoffpoints.import.form') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-upload"></i> Import rates
                </a>
                <a href="{{ route('transport.dropoffpoints.template') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Template
                </a>
                <a href="{{ route('transport.student-dropoffs.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-geo-alt"></i> Student drop-offs
                </a>
                <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-cash-coin"></i> Transport fees
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">All points</h5>
                    <small class="text-muted">Vehicles come from trips that pick up / drop students at each stop.</small>
                </div>
                <span class="input-chip">{{ $dropOffPoints->count() }} point(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th class="text-end">Two-way (KES/term)</th>
                                <th class="text-end">One-way (KES/term)</th>
                                <th>Students using</th>
                                <th>Vehicles (from trips)</th>
                                <th class="text-end" style="width:180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dropOffPoints as $point)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $point->name }}
                                        @if($point->isOwnMeans())
                                            <span class="badge bg-secondary">System</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ $point->two_way_amount !== null ? number_format((float) $point->two_way_amount, 2) : '—' }}
                                    </td>
                                    <td class="text-end">
                                        {{ $point->one_way_amount !== null ? number_format((float) $point->one_way_amount, 2) : '—' }}
                                    </td>
                                    <td>
                                        <strong>{{ (int) $point->students_using_count }}</strong>
                                        <small class="text-muted">
                                            (M {{ (int) $point->morning_users_count }} / E {{ (int) $point->evening_users_count }})
                                        </small>
                                    </td>
                                    <td>
                                        @if($point->trip_vehicles->isNotEmpty())
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($point->trip_vehicles as $label)
                                                    <span class="input-chip">{{ $label }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">None on trips yet</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('transport.dropoffpoints.edit', $point->id) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        @unless($point->isOwnMeans())
                                            <form action="{{ route('transport.dropoffpoints.destroy', $point->id) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Delete this drop-off point?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No drop-off points found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
