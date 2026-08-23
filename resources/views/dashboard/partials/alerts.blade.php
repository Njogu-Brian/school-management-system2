<div class="dash-card card mb-3">
  <div class="card-header"><strong>Alerts / Actions</strong></div>
  <div class="card-body">
    @forelse($operationalAlerts ?? [] as $alert)
      @if(!empty($alert['url']))
        <a href="{{ $alert['url'] }}" class="dash-list-item d-flex justify-content-between text-decoration-none">
          <span>{{ $alert['label'] }}</span>
          <i class="bi bi-arrow-right"></i>
        </a>
      @else
        <div class="dash-list-item">{{ $alert['label'] }}</div>
      @endif
    @empty
      <div class="erp-empty"><i class="bi bi-check-circle"></i>No operational alerts from current records.</div>
    @endforelse
  </div>
</div>
