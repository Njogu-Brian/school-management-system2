@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Salary Structure Details</h2>
      <small class="text-muted">View salary structure information</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('hr.payroll.salary-structures.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="{{ route('hr.payroll.salary-structures.edit', $structure->id) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil"></i> Edit
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Salary Structure Information</h5>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Staff Member</label>
              <div class="fw-semibold">{{ $structure->staff->name }}</div>
              <div class="small text-muted">{{ $structure->staff->department->name ?? '—' }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Status</label>
              <div>
                <span class="badge bg-{{ $structure->is_active ? 'success' : 'secondary' }} fs-6">
                  {{ $structure->is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">Salary Components</h6>
          <div class="row mb-3">
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Basic Salary</label>
              <div class="h5 text-primary mb-0">Ksh {{ number_format($structure->basic_salary, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Housing Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->housing_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Transport Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->transport_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Medical Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->medical_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Other Allowances</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->other_allowances, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Gross Salary</label>
              <div class="h4 text-success mb-0">Ksh {{ number_format($structure->gross_salary, 2) }}</div>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">Deductions</h6>
          <div class="row mb-3">
            <div class="col-md-4 mb-2">
              <label class="text-muted small">NSSF</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->nssf_deduction, 2) }}</div>
            </div>
            <div class="col-md-4 mb-2">
              <label class="text-muted small">NHIF</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->nhif_deduction, 2) }}</div>
            </div>
            <div class="col-md-4 mb-2">
              <label class="text-muted small">PAYE</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->paye_deduction, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Other Deductions</label>
              <div class="h6 mb-0">Ksh {{ number_format($structure->other_deductions, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Total Deductions</label>
              <div class="h5 text-danger mb-0">Ksh {{ number_format($structure->total_deductions, 2) }}</div>
            </div>
            <div class="col-12 mb-2">
              <label class="text-muted small">Net Salary</label>
              <div class="h3 text-primary mb-0">Ksh {{ number_format($structure->net_salary, 2) }}</div>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Effective From</label>
              <div>{{ $structure->effective_from->format('F d, Y') }}</div>
            </div>
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Effective To</label>
              <div>{{ $structure->effective_to ? $structure->effective_to->format('F d, Y') : 'Ongoing' }}</div>
            </div>
            @if($structure->notes)
            <div class="col-12 mb-3">
              <label class="text-muted small">Notes</label>
              <div class="bg-light p-2 rounded">{{ nl2br(e($structure->notes)) }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

