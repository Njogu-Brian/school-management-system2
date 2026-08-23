<div class="dash-card card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Recent Activity</strong>
  </div>
  <div class="card-body">
    @forelse($activity ?? [] as $item)
      <div class="dash-list-item d-flex justify-content-between gap-2">
        <div>
          @if(!empty($item['url']))
            <a href="{{ $item['url'] }}" class="fw-semibold text-decoration-none">{{ $item['title'] }}</a>
          @else
            <div class="fw-semibold">{{ $item['title'] }}</div>
          @endif
          <div class="small dash-muted">{{ $item['meta'] ?? '' }}</div>
        </div>
        <div class="text-end">
          <span class="dash-badge-soft">{{ $item['tag'] ?? '' }}</span>
          <div class="small dash-muted mt-1">
            @if(!empty($item['date']))
              {{ \Carbon\Carbon::parse($item['date'])->diffForHumans() }}
            @endif
          </div>
        </div>
      </div>
    @empty
      @if(!empty($widgetError))
        <div class="erp-empty">Unable to load this information.<div class="mt-2"><a class="btn btn-outline-primary btn-sm" href="{{ url()->current() }}">Retry</a></div></div>
      @else
        <div class="erp-empty"><i class="bi bi-clock-history"></i>No recent activity.</div>
      @endif
    @endforelse
  </div>
</div>
