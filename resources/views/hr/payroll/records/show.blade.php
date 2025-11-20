@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Payroll Record Details</h2>
      <small class="text-muted">View payroll record information</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('hr.payroll.records.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-primary" target="_blank">
        <i class="bi bi-file-earmark-pdf"></i> View Payslip
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header">
          <h5 class="mb-0">Payroll Information</h5>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="text-muted small">Staff Member</label>
              <div class="fw-semibold">{{ $record->staff->name }}</div>
              <div class="small text-muted">ID: {{ $record->staff->staff_id }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted small">Payroll Period</label>
              <div class="fw-semibold">{{ $record->payrollPeriod->period_name }}</div>
              <div class="small text-muted">Pay Date: {{ $record->payrollPeriod->pay_date->format('M d, Y') }}</div>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">Salary Breakdown</h6>
          <div class="row mb-3">
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Basic Salary</label>
              <div class="h5 text-primary mb-0">Ksh {{ number_format($record->basic_salary, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Housing Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->housing_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Transport Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->transport_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Medical Allowance</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->medical_allowance, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Other Allowances</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->other_allowances, 2) }}</div>
            </div>
            @if($record->bonus > 0)
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Bonus</label>
              <div class="h6 text-success mb-0">Ksh {{ number_format($record->bonus, 2) }}</div>
            </div>
            @endif
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Gross Salary</label>
              <div class="h4 text-success mb-0">Ksh {{ number_format($record->gross_salary, 2) }}</div>
            </div>
          </div>

          <hr>

          <h6 class="mb-3">Deductions</h6>
          <div class="row mb-3">
            <div class="col-md-4 mb-2">
              <label class="text-muted small">NSSF</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->nssf_deduction, 2) }}</div>
            </div>
            <div class="col-md-4 mb-2">
              <label class="text-muted small">NHIF</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->nhif_deduction, 2) }}</div>
            </div>
            <div class="col-md-4 mb-2">
              <label class="text-muted small">PAYE</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->paye_deduction, 2) }}</div>
            </div>
            <div class="col-md-4 mb-2">
              <label class="text-muted small">Other Deductions</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->other_deductions, 2) }}</div>
            </div>
            @if($record->advance_deduction > 0)
            <div class="col-md-4 mb-2">
              <label class="text-muted small">Advance Deduction</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->advance_deduction, 2) }}</div>
            </div>
            @endif
            @if($record->custom_deductions_total > 0)
            <div class="col-md-4 mb-2">
              <label class="text-muted small">Custom Deductions</label>
              <div class="h6 mb-0">Ksh {{ number_format($record->custom_deductions_total, 2) }}</div>
            </div>
            @endif
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Total Deductions</label>
              <div class="h5 text-danger mb-0">Ksh {{ number_format($record->total_deductions, 2) }}</div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="text-muted small">Net Salary</label>
              <div class="h3 text-primary mb-0">Ksh {{ number_format($record->net_salary, 2) }}</div>
            </div>
          </div>

          <hr>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Status</label>
              <div>
                <span class="badge bg-{{ $record->status === 'approved' ? 'success' : ($record->status === 'paid' ? 'info' : 'warning') }} fs-6">
                  {{ ucfirst($record->status) }}
                </span>
              </div>
            </div>
            @if($record->payslip_number)
            <div class="col-md-6 mb-3">
              <label class="text-muted small">Payslip Number</label>
              <div>{{ $record->payslip_number }}</div>
            </div>
            @endif
            @if($record->notes)
            <div class="col-12 mb-3">
              <label class="text-muted small">Notes</label>
              <div class="bg-light p-2 rounded">{{ nl2br(e($record->notes)) }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

