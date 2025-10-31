<div class="card shadow-sm mb-3">
  <div class="card-header bg-white d-flex justify-content-between">
    <strong>Announcements</strong>
    <a class="small" href="{{ route('announcements.index') }}">Manage</a>
  </div>
  <ul class="list-group list-group-flush">
    @forelse($announcements as $a)
      <li class="list-group-item">
        <div class="d-flex justify-content-between">
          <div>
            <div class="fw-semibold">{{ $a->title }}</div>
            <div class="small text-muted">{{ Str::limit($a->body, 90) }}</div>
          </div>
          <span class="small text-muted">{{ $a->start_date->format('d M') }}</span>
        </div>
      </li>
    @empty
      <li class="list-group-item text-muted">No current announcements</li>
    @endforelse
  </ul>
</div>
