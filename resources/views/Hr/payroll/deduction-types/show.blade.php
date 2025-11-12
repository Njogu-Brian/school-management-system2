@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Deduction Type Details</h2>
      <small class="text-muted">View deduction type information</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('hr.payroll.deduction-types.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="{{ route('hr.payroll.deduction-types.edit', $type->id) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i> Edit
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Deduction Type Information</h5>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Name</label>
              <div class="h5 mb-0">{{ $type->name }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Code</label>
              <div>
                @if($type->code)
                  <span class="badge bg-secondary fs-6">{{ $type->code }}</span>
                @else
                  <span class="text-muted">—</span>
                @endif
              </div>
            </div>
          </div>

          @if($type->description)
          <div class="mb-3">
            <label class="text-muted small">Description</label>
            <div>{{ $type->description }}</div>
          </div>
          @endif

          <hr>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Calculation Method</label>
              <div>
                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $type->calculation_method)) }}</span>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Default Value</label>
              <div>
                @if($type->calculation_method === 'fixed_amount')
                  <strong>Ksh {{ number_format($type->default_amount ?? 0, 2) }}</strong>
                @elseif($type->percentage)
                  <strong>{{ number_format($type->percentage, 2) }}%</strong>
                @else
                  <span class="text-muted">—</span>
                @endif
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Type</label>
              <div>
                @if($type->is_statutory)
                  <span class="badge bg-danger">Statutory</span>
                @else
                  <span class="badge bg-primary">Custom</span>
                @endif
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Status</label>
              <div>
                <span class="badge bg-{{ $type->is_active ? 'success' : 'secondary' }}">
                  {{ $type->is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Requires Approval</label>
              <div>
                <span class="badge bg-{{ $type->requires_approval ? 'warning' : 'success' }}">
                  {{ $type->requires_approval ? 'Yes' : 'No' }}
                </span>
              </div>
            </div>
            @if($type->notes)
            <div class="col-12 mb-3">
              <label class="text-muted small">Notes</label>
              <div class="bg-light p-2 rounded">{{ nl2br(e($type->notes)) }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>

      @if($type->customDeductions->count() > 0)
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0">Active Deductions ({{ $type->customDeductions->where('status', 'active')->count() }})</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Amount</th>
                  <th>Status</th>
                  <th>Effective From</th>
                </tr>
              </thead>
              <tbody>
                @foreach($type->customDeductions->take(10) as $deduction)
                  <tr>
                    <td>{{ $deduction->staff->name }}</td>
                    <td>Ksh {{ number_format($deduction->amount, 2) }}</td>
                    <td><span class="badge bg-{{ $deduction->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($deduction->status) }}</span></td>
                    <td>{{ $deduction->effective_from->format('M d, Y') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            @if($type->customDeductions->count() > 10)
              <div class="text-center mt-2">
                <small class="text-muted">Showing 10 of {{ $type->customDeductions->count() }} deductions</small>
              </div>
            @endif
          </div>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

