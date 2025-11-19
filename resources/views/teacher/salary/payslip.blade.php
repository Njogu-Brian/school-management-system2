@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Payslip</h2>
      <small class="text-muted">{{ $record->payrollPeriod->period_name ?? '—' }}</small>
      @if($record->payslip_number)
        <div class="badge bg-primary">{{ $record->payslip_number }}</div>
      @endif
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('teacher.salary.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <a href="{{ route('teacher.salary.payslip.download', $record->id) }}" class="btn btn-primary">
        <i class="bi bi-download"></i> Download PDF
      </a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="row mb-4">
        <div class="col-md-6">
          <h5 class="mb-3">Payroll Period</h5>
          <p class="mb-1"><strong>{{ $record->payrollPeriod->period_name ?? '—' }}</strong></p>
          <p class="text-muted">Pay Date: {{ $record->payrollPeriod->pay_date->format('M d, Y') ?? '—' }}</p>
        </div>
        <div class="col-md-6 text-end">
          <h5 class="mb-3">Staff Information</h5>
          <p class="mb-1"><strong>{{ $record->staff->full_name ?? '—' }}</strong></p>
          <p class="text-muted">ID: {{ $record->staff->staff_id ?? '—' }}</p>
        </div>
      </div>

      <hr>

      <div class="row mb-4">
        <div class="col-md-6">
          <h6 class="mb-3">Earnings</h6>
          <table class="table table-borderless">
            <tr>
              <td>Basic Salary</td>
              <td class="text-end">Ksh {{ number_format($record->basic_salary, 2) }}</td>
            </tr>
            @if($record->housing_allowance > 0)
            <tr>
              <td>Housing Allowance</td>
              <td class="text-end">Ksh {{ number_format($record->housing_allowance, 2) }}</td>
            </tr>
            @endif
            @if($record->transport_allowance > 0)
            <tr>
              <td>Transport Allowance</td>
              <td class="text-end">Ksh {{ number_format($record->transport_allowance, 2) }}</td>
            </tr>
            @endif
            @if($record->medical_allowance > 0)
            <tr>
              <td>Medical Allowance</td>
              <td class="text-end">Ksh {{ number_format($record->medical_allowance, 2) }}</td>
            </tr>
            @endif
            @if($record->other_allowances > 0)
            <tr>
              <td>Other Allowances</td>
              <td class="text-end">Ksh {{ number_format($record->other_allowances, 2) }}</td>
            </tr>
            @endif
            @if($record->bonus > 0)
            <tr>
              <td>Bonus</td>
              <td class="text-end">Ksh {{ number_format($record->bonus, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top">
              <td><strong>Gross Salary</strong></td>
              <td class="text-end"><strong class="text-success">Ksh {{ number_format($record->gross_salary, 2) }}</strong></td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <h6 class="mb-3">Deductions</h6>
          <table class="table table-borderless">
            @if($record->nssf_deduction > 0)
            <tr>
              <td>NSSF</td>
              <td class="text-end">Ksh {{ number_format($record->nssf_deduction, 2) }}</td>
            </tr>
            @endif
            @if($record->nhif_deduction > 0)
            <tr>
              <td>NHIF</td>
              <td class="text-end">Ksh {{ number_format($record->nhif_deduction, 2) }}</td>
            </tr>
            @endif
            @if($record->paye_deduction > 0)
            <tr>
              <td>PAYE</td>
              <td class="text-end">Ksh {{ number_format($record->paye_deduction, 2) }}</td>
            </tr>
            @endif
            @if($record->advance_deduction > 0)
            <tr>
              <td>Advance Deduction</td>
              <td class="text-end">Ksh {{ number_format($record->advance_deduction, 2) }}</td>
            </tr>
            @endif
            @if($record->other_deductions > 0)
            <tr>
              <td>Other Deductions</td>
              <td class="text-end">Ksh {{ number_format($record->other_deductions, 2) }}</td>
            </tr>
            @endif
            @if($record->custom_deductions_total > 0)
            <tr>
              <td>Custom Deductions</td>
              <td class="text-end">Ksh {{ number_format($record->custom_deductions_total, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top">
              <td><strong>Total Deductions</strong></td>
              <td class="text-end"><strong class="text-danger">Ksh {{ number_format($record->total_deductions, 2) }}</strong></td>
            </tr>
          </table>
        </div>
      </div>

      <hr>

      <div class="row">
        <div class="col-12 text-end">
          <h4 class="mb-0">Net Salary: <span class="text-primary">Ksh {{ number_format($record->net_salary, 2) }}</span></h4>
        </div>
      </div>

      @if($record->notes)
      <hr>
      <div class="row">
        <div class="col-12">
          <h6>Notes</h6>
          <p class="text-muted">{{ $record->notes }}</p>
        </div>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection

