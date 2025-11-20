@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Payroll Period: {{ $period->period_name }}</h2>
      <small class="text-muted">View and manage payroll period</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      @if($period->status === 'draft')
        <form action="{{ route('hr.payroll.periods.process', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Process payroll for this period? This will generate payroll records for all active staff.')">
          @csrf
          <button type="submit" class="btn btn-success">
            <i class="bi bi-play-circle"></i> Process Payroll
          </button>
        </form>
      @endif
      @if($period->status === 'completed')
        <form action="{{ route('hr.payroll.periods.lock', $period->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Lock this payroll period? Locked periods cannot be modified.')">
          @csrf
          <button type="submit" class="btn btn-danger">
            <i class="bi bi-lock"></i> Lock Period
          </button>
        </form>
      @endif
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row mb-3">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Staff</h6>
          <h3 class="mb-0">{{ $period->staff_count ?? 0 }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Gross</h6>
          <h3 class="mb-0">Ksh {{ number_format($period->total_gross ?? 0, 2) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-danger text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Deductions</h6>
          <h3 class="mb-0">Ksh {{ number_format($period->total_deductions ?? 0, 2) }}</h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <h6 class="card-subtitle mb-2 text-white-50">Total Net</h6>
          <h3 class="mb-0">Ksh {{ number_format($period->total_net ?? 0, 2) }}</h3>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0">Period Information</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="text-muted small">Period Name</label>
          <div class="fw-semibold">{{ $period->period_name }}</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted small">Status</label>
          <div>
            @php
              $badgeColors = [
                'draft' => 'secondary',
                'processing' => 'warning',
                'completed' => 'success',
                'locked' => 'danger'
              ];
              $badge = $badgeColors[$period->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $badge }} fs-6">{{ ucfirst($period->status) }}</span>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted small">Start Date</label>
          <div>{{ $period->start_date->format('F d, Y') }}</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted small">End Date</label>
          <div>{{ $period->end_date->format('F d, Y') }}</div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="text-muted small">Pay Date</label>
          <div>{{ $period->pay_date->format('F d, Y') }}</div>
        </div>
        @if($period->processed_at)
        <div class="col-md-6 mb-3">
          <label class="text-muted small">Processed At</label>
          <div>{{ $period->processed_at->format('F d, Y H:i') }}</div>
          @if($period->processedBy)
            <div class="small text-muted">By: {{ $period->processedBy->name }}</div>
          @endif
        </div>
        @endif
      </div>
    </div>
  </div>

  @if($period->payrollRecords->count() > 0)
  <div class="card shadow-sm mt-3">
    <div class="card-header">
      <h5 class="mb-0">Payroll Records ({{ $period->payrollRecords->count() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Staff</th>
              <th>Gross Salary</th>
              <th>Deductions</th>
              <th>Net Salary</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($period->payrollRecords as $record)
              <tr>
                <td>{{ $record->staff->name }}</td>
                <td>Ksh {{ number_format($record->gross_salary, 2) }}</td>
                <td>Ksh {{ number_format($record->total_deductions, 2) }}</td>
                <td><strong>Ksh {{ number_format($record->net_salary, 2) }}</strong></td>
                <td><span class="badge bg-{{ $record->status === 'approved' ? 'success' : 'warning' }}">{{ ucfirst($record->status) }}</span></td>
                <td class="text-end">
                  <a href="{{ route('hr.payroll.records.show', $record->id) }}" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

