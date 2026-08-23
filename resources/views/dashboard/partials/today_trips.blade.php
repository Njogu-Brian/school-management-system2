<div class="dash-card card mb-3">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Today's operations</strong>
    @if(Route::has('transport.trips.index'))
      <a class="small dash-btn-ghost" href="{{ route('transport.trips.index') }}">All trips</a>
    @endif
  </div>
  <div class="table-responsive erp-invoice-table">
    <table class="table table-sm mb-0 dash-table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Route</th>
          <th>Vehicle</th>
          <th>Driver</th>
          <th>Students</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transport['today_trips'] ?? [] as $trip)
          <tr class="{{ !empty($trip->missing_driver) || !empty($trip->missing_vehicle) ? 'table-warning' : '' }}">
            <td>{{ $trip->time }}</td>
            <td>
              @if(!empty($trip->url))
                <a href="{{ $trip->url }}">{{ $trip->route }}</a>
              @else
                {{ $trip->route }}
              @endif
            </td>
            <td>{{ $trip->vehicle ?: 'Unassigned' }}</td>
            <td>{{ $trip->driver ?: 'Unassigned' }}</td>
            <td>{{ $trip->students }}</td>
            <td><span class="erp-status {{ str_replace('_', '-', $trip->status) }}">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-muted">No trips are scheduled for today.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="erp-invoice-cards">
    @forelse($transport['today_trips'] ?? [] as $trip)
      <div class="erp-invoice-card {{ !empty($trip->missing_driver) || !empty($trip->missing_vehicle) ? 'border-warning' : '' }}">
        <div class="d-flex justify-content-between gap-2">
          <div class="fw-semibold">{{ $trip->route }}</div>
          <span class="erp-status">{{ $trip->time }}</span>
        </div>
        <div class="small dash-muted mt-1">{{ $trip->vehicle ?: 'No vehicle' }} · {{ $trip->driver ?: 'No driver' }} · {{ $trip->students }} students</div>
        @if(!empty($trip->url))
          <a href="{{ $trip->url }}" class="btn btn-outline-primary btn-sm mt-2">Open trip</a>
        @endif
      </div>
    @empty
      <div class="erp-empty"><i class="bi bi-bus-front"></i>No trips are scheduled for today.</div>
    @endforelse
  </div>
</div>
