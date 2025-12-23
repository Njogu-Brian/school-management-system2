<div class="dash-card card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Transport Snapshot</strong>
        @isset($transportRoutesIndex)
            <a class="small" href="{{ $transportRoutesIndex }}">View</a>
        @else
            @if(Route::has('transport.trips.index'))
                <a class="small" href="{{ route('transport.trips.index') }}">View</a>
            @endif
        @endisset
    </div>

    @php
        $tripsLast30 = (int)($transport['trips_last_30'] ?? 0);
        $vehicles    = (int)($transport['vehicles'] ?? 0);
    @endphp

    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <div class="small text-muted">Trips (last 30 days)</div>
            <div class="fs-5 fw-semibold">{{ $tripsLast30 }}</div>
        </div>

        <div class="d-flex justify-content-between">
            <div class="small text-muted">Vehicles</div>
            <div class="fs-5 fw-semibold">{{ $vehicles }}</div>
        </div>
    </div>

    <div class="card-footer bg-transparent border-0 pt-0 small text-muted">
        * “Active Trips” is hidden because the <code>trips</code> table has no <code>status</code> column yet.
    </div>
</div>
