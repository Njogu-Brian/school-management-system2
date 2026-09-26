@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @php
        $canManageExtraIncome = auth()->user()?->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance Officer', 'Director']);
        $activityActions = '';
        if ($canManageExtraIncome) {
            $activityActions .= '<a href="' . route('finance.extra-income.index') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-piggy-bank"></i> Extra income</a>';
        }
        $activityActions .= '<a href="' . route('activity-fees.parent-requests.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-person-check"></i> Parent join / leave</a>';
    @endphp
    @include('finance.partials.header', [
        'title' => 'Activity fees',
        'icon' => 'bi bi-trophy',
        'subtitle' => 'Optional programmes linked to voteheads. Rosters use students with a billed optional fee for the votehead in year ' . $year . ', term ' . $term . '.',
        'actions' => $activityActions,
    ])

    @include('finance.invoices.partials.alerts')

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <form method="GET" action="{{ route('activity-fees.index') }}" class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="finance-form-label">Year</label>
          <input type="number" name="year" class="finance-form-control" value="{{ $year }}" min="2000" max="2100">
        </div>
        <div class="col-md-3">
          <label class="finance-form-label">Term</label>
          <select name="term" class="finance-form-select">
            @foreach([1, 2, 3] as $t)
              <option value="{{ $t }}" {{ (int) $term === $t ? 'selected' : '' }}>Term {{ $t }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-finance btn-finance-primary">Apply</button>
        </div>
      </form>
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-body">
        <p class="text-muted small mb-3">
          @if($canManageExtraIncome ?? false)
            Trips, fun days, and swimming that arrive mixed with school fees are set up under <a href="{{ route('finance.extra-income.index') }}">Extra income</a>, then split from the bank or M-Pesa transaction.
          @endif
          Ongoing programmes use a votehead marked as an <strong>activity fee</strong> under <a href="{{ route('finance.voteheads.index') }}">Finance → Voteheads</a>.
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
                    <a href="{{ route('activity-fees.show', ['votehead' => $vh, 'year' => $year, 'term' => $term]) }}" class="btn btn-sm btn-finance btn-finance-outline">Roster</a>
                    <a href="{{ route('activity-fees.attendance', ['votehead' => $vh, 'year' => $year, 'term' => $term]) }}" class="btn btn-sm btn-finance btn-finance-success">Attendance</a>
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
