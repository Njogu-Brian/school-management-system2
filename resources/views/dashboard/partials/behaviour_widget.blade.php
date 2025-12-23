<div class="dash-card card mb-3 h-100">
    <div class="card-header">
        <strong>Behaviour (last 7 days)</strong>
    </div>

    @php
        $minor    = (int)($behaviour['minor'] ?? 0);
        $moderate = (int)($behaviour['moderate'] ?? 0);
        $major    = (int)($behaviour['major'] ?? 0);
        $recent   = $behaviour['recent'] ?? collect();
        // helper for badge color
        $badge = function ($sev) {
            return match($sev) {
                'major'    => 'bg-danger',
                'moderate' => 'bg-warning text-dark',
                default    => 'bg-success',
            };
        };
    @endphp

    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap mb-2">
            <div><span class="badge bg-success">{{ $minor }}</span> <span class="small dash-muted">Minor</span></div>
            <div><span class="badge bg-warning text-dark">{{ $moderate }}</span> <span class="small dash-muted">Moderate</span></div>
            <div><span class="badge bg-danger">{{ $major }}</span> <span class="small dash-muted">Major</span></div>
        </div>

        <ul class="list-unstyled mt-2 mb-0">
            @forelse($recent as $b)
                <li class="mb-2 d-flex align-items-start">
                    <span class="badge {{ $badge($b->severity ?? 'minor') }} me-2">{{ ucfirst($b->severity ?? 'minor') }}</span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $b->student_name ?? 'Student' }}</div>
                        <div class="small text-muted">
                            {{ $b->behaviour ?? 'Behaviour' }}
                            @if(!empty($b->date)) · {{ \Carbon\Carbon::parse($b->date)->format('d M') }} @endif
                        </div>
                        @if(!empty($b->note))
                            <div class="small">{{ $b->note }}</div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="text-muted small">No behaviour records in the last 7 days.</li>
            @endforelse
        </ul>
    </div>
</div>
