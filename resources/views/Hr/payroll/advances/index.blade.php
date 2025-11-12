@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Staff Advances</h2>
      <small class="text-muted">Manage staff advance loans and repayments</small>
    </div>
    <a href="{{ route('hr.payroll.advances.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> New Advance
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Staff</label>
          <select name="staff_id" class="form-select">
            <option value="">All Staff</option>
            @foreach($staff as $s)
              <option value="{{ $s->id }}" @selected(request('staff_id')==$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
            <option value="approved" @selected(request('status')==='approved')>Approved</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="completed" @selected(request('status')==='completed')>Completed</option>
            <option value="cancelled" @selected(request('status')==='cancelled')>Cancelled</option>
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
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Advances</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Staff</th>
              <th>Amount</th>
              <th>Repaid</th>
              <th>Balance</th>
              <th>Repayment Method</th>
              <th>Date</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($advances as $advance)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $advance->staff->name }}</div>
                  <div class="small text-muted">{{ $advance->purpose ?? '—' }}</div>
                </td>
                <td>
                  <strong>Ksh {{ number_format($advance->amount, 2) }}</strong>
                </td>
                <td>
                  <span class="text-success">Ksh {{ number_format($advance->amount_repaid, 2) }}</span>
                </td>
                <td>
                  <strong class="text-primary">Ksh {{ number_format($advance->balance, 2) }}</strong>
                </td>
                <td>
                  <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $advance->repayment_method)) }}</span>
                  @if($advance->monthly_deduction_amount)
                    <div class="small text-muted">Ksh {{ number_format($advance->monthly_deduction_amount, 2) }}/month</div>
                  @endif
                </td>
                <td>
                  <div>{{ $advance->advance_date->format('M d, Y') }}</div>
                  @if($advance->expected_completion_date)
                    <div class="small text-muted">Due: {{ $advance->expected_completion_date->format('M d, Y') }}</div>
                  @endif
                </td>
                <td>
                  @php
                    $badgeColors = [
                      'pending' => 'warning',
                      'approved' => 'info',
                      'active' => 'success',
                      'completed' => 'secondary',
                      'cancelled' => 'danger'
                    ];
                    $badge = $badgeColors[$advance->status] ?? 'secondary';
                  @endphp
                  <span class="badge bg-{{ $badge }}">{{ ucfirst($advance->status) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('hr.payroll.advances.show', $advance->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    @if($advance->status === 'pending')
                      <a href="{{ route('hr.payroll.advances.edit', $advance->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No advances found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($advances->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $advances->firstItem() }}–{{ $advances->lastItem() }} of {{ $advances->total() }} advances
        </div>
        {{ $advances->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

