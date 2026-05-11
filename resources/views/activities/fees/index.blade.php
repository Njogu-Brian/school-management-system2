@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Activity fees',
        'icon' => 'bi bi-trophy',
        'subtitle' => 'Optional programmes linked to voteheads. Rosters use students with a billed optional fee for the votehead in year ' . $year . ', term ' . $term . '.',
    ])

    @include('finance.invoices.partials.alerts')

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-body">
        <p class="text-muted small mb-3">
          Mark a votehead as an <strong>activity fee</strong> under <a href="{{ route('finance.voteheads.index') }}">Finance → Voteheads</a>.
          Assign the optional fee to students as usual; they will appear here for lists and attendance.
        </p>
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Activity (votehead)</th>
                <th class="text-end">On roster</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($voteheads as $vh)
                @php $n = (int) ($counts[$vh->id] ?? 0); @endphp
                <tr>
                  <td class="fw-semibold">{{ $vh->name }}</td>
                  <td class="text-end">{{ $n }}</td>
                  <td class="text-end">
                    <a href="{{ route('activity-fees.show', $vh) }}" class="btn btn-sm btn-finance btn-finance-outline">Roster</a>
                    <a href="{{ route('activity-fees.attendance', $vh) }}" class="btn btn-sm btn-finance btn-finance-success">Attendance</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">
                    No activity-fee voteheads yet. Edit a votehead and enable &quot;Activity fee&quot;.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
