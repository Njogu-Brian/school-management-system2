@php
  $matches = $matches ?? [];
  if ($matches instanceof \Illuminate\Support\Collection) {
      $matches = $matches->map(fn ($m) => is_array($m) ? $m : $m->toArray())->all();
  }
@endphp
@if(!empty($matches))
  <ul class="list-unstyled mb-0">
    @foreach($matches as $match)
      <li class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div>
          <div class="fw-semibold">
            {{ $match['full_name'] ?? 'Unknown' }}
            @if(!empty($match['admission_number']))
              <span class="text-muted">({{ $match['admission_number'] }})</span>
            @elseif(!empty($match['application_no']))
              <span class="text-muted">({{ $match['application_no'] }})</span>
            @endif
          </div>
          <div class="small text-muted">
            {{ $match['source_label'] ?? ucfirst(str_replace('_', ' ', $match['source'] ?? '')) }}
            @if(!empty($match['status'])) · {{ $match['status'] }} @endif
            @if(!empty($match['classroom'])) · {{ $match['classroom'] }} @endif
            @if(!empty($match['application_status'])) · {{ str_replace('_', ' ', $match['application_status']) }} @endif
          </div>
          <div class="small">
            <span class="badge {{ ($match['confidence'] ?? '') === 'high' ? 'bg-danger' : 'bg-warning text-dark' }}">
              {{ $match['reason_label'] ?? $match['reason'] ?? 'Possible match' }}
            </span>
          </div>
        </div>
        @if(!empty($match['url']))
          <a href="{{ $match['url'] }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Open</a>
        @endif
      </li>
    @endforeach
  </ul>
@endif
