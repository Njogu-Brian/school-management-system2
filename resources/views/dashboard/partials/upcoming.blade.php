<div class="dash-card card mb-3">
  <div class="card-header"><strong>Coming Up</strong></div>
  <div class="list-group list-group-flush">
    @forelse($upcoming as $it)
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">{{ $it['title'] }}</div>
          <div class="small text-muted">{{ $it['meta'] }}</div>
        </div>
        <span class="badge bg-light text-dark">{{ \Carbon\Carbon::parse($it['date'])->format('d M') }}</span>
      </div>
    @empty
      <div class="list-group-item text-muted">Nothing scheduled.</div>
    @endforelse
  </div>
</div>
