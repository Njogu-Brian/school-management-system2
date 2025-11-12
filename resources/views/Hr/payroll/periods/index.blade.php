@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Payroll Periods</h2>
      <small class="text-muted">Manage payroll processing periods</small>
    </div>
    <a href="{{ route('hr.payroll.periods.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> New Period
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Payroll Periods</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Period</th>
              <th>Year/Month</th>
              <th>Period Dates</th>
              <th>Pay Date</th>
              <th>Staff Count</th>
              <th>Total Net</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($periods as $period)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $period->period_name }}</div>
                </td>
                <td>
                  <div>{{ $period->year }}</div>
                  <div class="small text-muted">Month {{ $period->month }}</div>
                </td>
                <td>
                  <div>{{ $period->start_date->format('M d') }} - {{ $period->end_date->format('M d, Y') }}</div>
                </td>
                <td>{{ $period->pay_date->format('M d, Y') }}</td>
                <td>
                  <span class="badge bg-info">{{ $period->staff_count ?? 0 }}</span>
                </td>
                <td>
                  <strong>Ksh {{ number_format($period->total_net ?? 0, 2) }}</strong>
                </td>
                <td>
                  @php
                    $badgeColors = [
                      'draft' => 'secondary',
                      'processing' => 'warning',
                      'completed' => 'success',
                      'locked' => 'danger'
                    ];
                    $badge = $badgeColors[$period->status] ?? 'secondary';
                  @endphp
                  <span class="badge bg-{{ $badge }}">{{ ucfirst($period->status) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('hr.payroll.periods.show', $period->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    @if($period->status === 'draft')
                      <form action="{{ route('hr.payroll.periods.process', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Process payroll for this period?')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Process">
                          <i class="bi bi-play-circle"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No payroll periods found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($periods->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $periods->firstItem() }}–{{ $periods->lastItem() }} of {{ $periods->total() }} periods
        </div>
        {{ $periods->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

