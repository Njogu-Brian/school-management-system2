@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero mb-3">
      <span class="crumb">Transport</span>
      <h2 class="mb-1">What is happening with transport today?</h2>
      <p class="mb-0">Live trips, missing assignments and vehicle coverage.</p>
    </div>

    <div class="row g-3 mb-3">
      @foreach([
        ['Vehicles', $transport['vehicles'] ?? 0, 'bi-bus-front', Route::has('transport.vehicles.index') ? route('transport.vehicles.index') : null],
        ['Drivers', $transport['drivers'] ?? 0, 'bi-person-badge', null],
        ['Routes', $transport['routes'] ?? 0, 'bi-signpost-2', null],
        ['Students assigned', $transport['students_assigned'] ?? 0, 'bi-people', Route::has('transport.student-assignments.index') ? route('transport.student-assignments.index') : null],
        ['Trips today', $transport['trips_scheduled_today'] ?? 0, 'bi-calendar-day', Route::has('transport.trips.index') ? route('transport.trips.index') : null],
        ['Runs today', $transport['trip_runs_today'] ?? 0, 'bi-geo', null],
      ] as [$label, $value, $icon, $href])
        <div class="col-6 col-lg-4 col-xxl-2">
          @php $tag = $href ? 'a' : 'div'; @endphp
          <{{ $tag }} @if($href) href="{{ $href }}" @endif class="dash-card card erp-kpi h-100 text-decoration-none">
            <div class="card-body">
              <div class="dash-muted small mb-1"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
              <div class="erp-kpi-value">{{ number_format((int) $value) }}</div>
            </div>
          </{{ $tag }}>
        </div>
      @endforeach
    </div>

    @include('dashboard.partials.today_trips')

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="dash-card card">
          <div class="card-header"><strong>Operational alerts</strong></div>
          <div class="card-body">
            @forelse($transport['alerts'] ?? [] as $alert)
              @if(!empty($alert['url']))
                <a href="{{ $alert['url'] }}" class="dash-list-item d-flex justify-content-between text-decoration-none">
                  <span>{{ $alert['count'] }} {{ $alert['label'] }}</span>
                  <i class="bi bi-arrow-right"></i>
                </a>
              @else
                <div class="dash-list-item">{{ $alert['count'] }} {{ $alert['label'] }}</div>
              @endif
            @empty
              <div class="erp-empty"><i class="bi bi-check-circle"></i>No transport alerts from current records.</div>
            @endforelse
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.announcements')
      </div>
    </div>
  </div>
</div>
@endsection
