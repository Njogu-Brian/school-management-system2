@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Parent activity requests',
        'icon' => 'bi bi-person-check',
        'subtitle' => 'Confirm or decline when a parent asks to join or leave ballet, skating, music, yoghurt, or other activity fees.',
        'actions' => '<a href="' . route('activity-fees.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-trophy"></i> Activity rosters</a>',
    ])

    @include('finance.invoices.partials.alerts')

    <div class="d-flex flex-wrap gap-2 mb-3">
      @foreach(['pending' => 'Waiting', 'approved' => 'Confirmed', 'rejected' => 'Declined', 'all' => 'All'] as $key => $label)
        <a href="{{ route('activity-fees.parent-requests.index', ['status' => $key]) }}"
           class="btn btn-sm {{ $status === $key ? 'btn-finance' : 'btn-finance-outline' }}">
          {{ $label }}
          @if($key === 'pending' && $pendingCount > 0)
            <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
          @endif
        </a>
      @endforeach
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-body">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>When</th>
                <th>Child</th>
                <th>Activity</th>
                <th>Term</th>
                <th>Change</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($requests as $item)
                <tr>
                  <td class="small text-muted">{{ $item->created_at?->format('d M Y H:i') }}</td>
                  <td>
                    <div class="fw-semibold">{{ $item->student?->full_name }}</div>
                    <div class="small text-muted">{{ $item->student?->admission_number }} · {{ $item->student?->classroom?->name }}</div>
                    @if($item->requestedBy)
                      <div class="small text-muted">Requested by {{ $item->requestedBy->name }}</div>
                    @endif
                  </td>
                  <td class="fw-semibold">{{ $item->votehead?->name }}</td>
                  <td>Term {{ $item->term }} {{ $item->year }}</td>
                  <td>
                    @if($item->action === 'leave')
                      <span class="badge bg-danger-subtle text-danger">Leave</span>
                    @else
                      <span class="badge bg-success-subtle text-success">Join</span>
                    @endif
                  </td>
                  <td class="text-end">KES {{ number_format((float) $item->requested_amount, 0) }}</td>
                  <td>
                    <span class="badge {{ $item->status === 'pending' ? 'bg-warning text-dark' : ($item->status === 'approved' ? 'bg-success' : 'bg-secondary') }}">
                      {{ ucfirst($item->status) }}
                    </span>
                    @if($item->review_note)
                      <div class="small text-muted mt-1">{{ $item->review_note }}</div>
                    @endif
                  </td>
                  <td class="text-end">
                    @if($item->status === 'pending')
                      <form method="POST" action="{{ route('activity-fees.parent-requests.approve', $item) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-finance btn-finance-success" onclick="return confirm('Confirm this activity change? The parent will be notified.')">Confirm</button>
                      </form>
                      <form method="POST" action="{{ route('activity-fees.parent-requests.reject', $item) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Decline this request? The parent will be notified.')">Decline</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">No {{ $status === 'all' ? '' : $status }} requests.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">{{ $requests->links() }}</div>
      </div>
    </div>
  </div>
</div>
@endsection
