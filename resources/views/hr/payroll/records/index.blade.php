@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Payroll Records</h2>
      <small class="text-muted">View and manage payroll records</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Payroll Period</label>
          <select name="payroll_period_id" class="form-select">
            <option value="">All Periods</option>
            @foreach($periods as $p)
              <option value="{{ $p->id }}" @selected(request('payroll_period_id')==$p->id)>{{ $p->period_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="draft" @selected(request('status')==='draft')>Draft</option>
            <option value="approved" @selected(request('status')==='approved')>Approved</option>
            <option value="paid" @selected(request('status')==='paid')>Paid</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filter
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Payroll Records</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Period</th>
              <th>Staff</th>
              <th>Basic Salary</th>
              <th>Gross Salary</th>
              <th>Total Deductions</th>
              <th>Net Salary</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($records as $record)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $record->payrollPeriod->period_name }}</div>
                  <div class="small text-muted">{{ $record->payrollPeriod->pay_date->format('M d, Y') }}</div>
                </td>
                <td>
                  <div class="fw-semibold">{{ $record->staff->name }}</div>
                  <div class="small text-muted">{{ $record->staff->staff_id }}</div>
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
                    <a href="{{ route('hr.payroll.records.show', $record->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-sm btn-outline-primary" title="Payslip" target="_blank">
                      <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No payroll records found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($records->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $records->firstItem() }}–{{ $records->lastItem() }} of {{ $records->total() }} records
        </div>
        {{ $records->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

