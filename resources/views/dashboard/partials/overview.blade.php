@php
  $items = $overview ?? [];
@endphp
<div class="dash-card card h-100">
  <div class="card-header"><strong>School Overview</strong></div>
  <div class="card-body">
    @if(empty($items))
      <div class="erp-empty"><i class="bi bi-building"></i>No overview data available.</div>
    @else
      <div class="row g-2">
        @foreach([
          ['classrooms', 'Classrooms', 'bi-building'],
          ['streams', 'Streams', 'bi-diagram-3'],
          ['subjects', 'Subjects', 'bi-journal-text'],
          ['clubs', 'Clubs', 'bi-trophy'],
          ['vehicles', 'Buses', 'bi-bus-front'],
          ['trips', 'Trips', 'bi-signpost-2'],
          ['visitors_today', 'Visitors today', 'bi-person-walking'],
        ] as [$key, $label, $icon])
          @continue(!array_key_exists($key, $items))
          <div class="col-6 col-md-4">
            <div class="erp-overview-item">
              <div class="small dash-muted"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
              <div class="value">{{ number_format((int) $items[$key]) }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
