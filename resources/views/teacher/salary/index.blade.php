@extends('layouts.app')

@push('styles')
    @if(request()->routeIs('senior_teacher.*'))
        @include('senior_teacher.partials.styles')
    @endif
@endpush

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Salary & Payslips</h2>
      <small class="text-muted">View your salary information and payslips</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-3">
    {{-- Current Salary Structure --}}
    @if($salaryStructure)
      <div class="col-md-12">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Current Salary Structure</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 mb-3">
                <label class="text-muted small">Basic Salary</label>
                <div class="h5 text-primary mb-0">Ksh {{ number_format($salaryStructure->basic_salary, 2) }}</div>
              </div>
              <div class="col-md-3 mb-3">
                <label class="text-muted small">Gross Salary</label>
                <div class="h5 text-success mb-0">Ksh {{ number_format($salaryStructure->gross_salary, 2) }}</div>
              </div>
              <div class="col-md-3 mb-3">
                <label class="text-muted small">Net Salary</label>
                <div class="h5 text-info mb-0">Ksh {{ number_format($salaryStructure->net_salary, 2) }}</div>
              </div>
              <div class="col-md-3 mb-3">
                <label class="text-muted small">Effective From</label>
                <div class="fw-semibold">{{ $salaryStructure->effective_from->format('M d, Y') }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @else
      <div class="col-12">
        <div class="alert alert-info mb-0">
          <i class="bi bi-info-circle"></i> No active salary structure found.
        </div>
      </div>
    @endif

    {{-- Payroll Records --}}
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Payslips ({{ $payrollRecords->total() }})</h5>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Period</th>
                  <th>Pay Date</th>
                  <th>Basic Salary</th>
                  <th>Gross Salary</th>
                  <th>Total Deductions</th>
                  <th>Net Salary</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($payrollRecords as $record)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $record->payrollPeriod->period_name ?? '—' }}</div>
                    </td>
                    <td>
                      <div>{{ $record->payrollPeriod->pay_date->format('M d, Y') ?? '—' }}</div>
                    </td>
                    <td>Ksh {{ number_format($record->basic_salary, 2) }}</td>
                    <td><strong class="text-success">Ksh {{ number_format($record->gross_salary, 2) }}</strong></td>
                    <td><span class="text-danger">Ksh {{ number_format($record->total_deductions, 2) }}</span></td>
                    <td><strong class="text-primary">Ksh {{ number_format($record->net_salary, 2) }}</strong></td>
                    <td>
                      <span class="badge bg-{{ $record->status === 'approved' ? 'success' : ($record->status === 'paid' ? 'info' : 'warning') }}">
                        {{ ucfirst($record->status) }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <a href="{{ route('teacher.salary.payslip', $record->id) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="View Payslip">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('teacher.salary.payslip.download', $record->id) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF">
                          <i class="bi bi-download"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No payslips found.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if($payrollRecords->hasPages())
          <div class="card-footer">
            {{ $payrollRecords->withQueryString()->links() }}
          </div>
        @endif
      </div>
    </div>

    {{-- Salary History --}}
    @if($salaryHistory->count() > 1)
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Salary History</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Effective From</th>
                    <th>Effective To</th>
                    <th>Basic Salary</th>
                    <th>Gross Salary</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($salaryHistory->take(10) as $history)
                    <tr>
                      <td>{{ $history->effective_from->format('M d, Y') }}</td>
                      <td>{{ $history->effective_to ? $history->effective_to->format('M d, Y') : 'Ongoing' }}</td>
                      <td>Ksh {{ number_format($history->basic_salary, 2) }}</td>
                      <td>Ksh {{ number_format($history->gross_salary, 2) }}</td>
                      <td>
                        <span class="badge bg-{{ $history->is_active ? 'success' : 'secondary' }}">
                          {{ $history->is_active ? 'Active' : 'Inactive' }}
                        </span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection

