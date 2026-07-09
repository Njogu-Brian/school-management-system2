@php
    $staff = $record->staff;
    $period = $record->payrollPeriod;
    $forPdf = $forPdf ?? false;
@endphp

<div class="row mb-4">
    <div class="col-6">
        <h5 class="mb-2">{{ config('app.name', 'School ERP') }}</h5>
        <div class="text-muted small">Official payslip</div>
        @if($record->payslip_number)
            <div class="mt-2"><strong>{{ $record->payslip_number }}</strong></div>
        @endif
    </div>
    <div class="col-6 text-end">
        <div class="fw-semibold">{{ $period->period_name ?? '—' }}</div>
        <div class="small text-muted">
            Pay date:
            {{ $period?->pay_date ? $period->pay_date->format('d M Y') : '—' }}
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-6">
        <div class="text-muted small">Employee</div>
        <div class="fw-semibold">{{ $staff?->name ?? ('Staff #'.$record->staff_id) }}</div>
        <div class="small text-muted">ID: {{ $staff?->staff_id ?? '—' }}</div>
        @if($staff?->kra_pin)
            <div class="small text-muted">KRA PIN: {{ $staff->kra_pin }}</div>
        @endif
    </div>
    <div class="col-6 text-end">
        <div class="text-muted small">Status</div>
        <div class="fw-semibold">{{ ucfirst($record->status) }}</div>
        @if($staff?->nssf)
            <div class="small text-muted">NSSF: {{ $staff->nssf }}</div>
        @endif
        @if($staff?->nhif)
            <div class="small text-muted">SHIF/NHIF: {{ $staff->nhif }}</div>
        @endif
    </div>
</div>

<hr>

<div class="row">
    <div class="col-6">
        <h6 class="mb-3">Earnings</h6>
        <table class="table table-sm {{ $forPdf ? '' : 'table-borderless' }} mb-0">
            <tr>
                <td>Basic Salary</td>
                <td class="text-end">Ksh {{ number_format((float) $record->basic_salary, 2) }}</td>
            </tr>
            @if((float) $record->housing_allowance > 0)
            <tr>
                <td>Housing Allowance</td>
                <td class="text-end">Ksh {{ number_format((float) $record->housing_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->transport_allowance > 0)
            <tr>
                <td>Transport Allowance</td>
                <td class="text-end">Ksh {{ number_format((float) $record->transport_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->medical_allowance > 0)
            <tr>
                <td>Medical Allowance</td>
                <td class="text-end">Ksh {{ number_format((float) $record->medical_allowance, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->other_allowances > 0)
            <tr>
                <td>Other Allowances</td>
                <td class="text-end">Ksh {{ number_format((float) $record->other_allowances, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->bonus > 0)
            <tr>
                <td>Bonus</td>
                <td class="text-end">Ksh {{ number_format((float) $record->bonus, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top">
                <td><strong>Gross Salary</strong></td>
                <td class="text-end"><strong>Ksh {{ number_format((float) $record->gross_salary, 2) }}</strong></td>
            </tr>
        </table>
    </div>
    <div class="col-6">
        <h6 class="mb-3">Deductions</h6>
        <table class="table table-sm {{ $forPdf ? '' : 'table-borderless' }} mb-0">
            @if((float) $record->nssf_deduction > 0)
            <tr>
                <td>NSSF</td>
                <td class="text-end">Ksh {{ number_format((float) $record->nssf_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float) ($record->shif_deduction ?? 0) > 0)
            <tr>
                <td>SHIF</td>
                <td class="text-end">Ksh {{ number_format((float) $record->shif_deduction, 2) }}</td>
            </tr>
            @elseif((float) $record->nhif_deduction > 0)
            <tr>
                <td>NHIF</td>
                <td class="text-end">Ksh {{ number_format((float) $record->nhif_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float) ($record->housing_levy_deduction ?? 0) > 0)
            <tr>
                <td>Housing Levy</td>
                <td class="text-end">Ksh {{ number_format((float) $record->housing_levy_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->paye_deduction > 0)
            <tr>
                <td>PAYE</td>
                <td class="text-end">Ksh {{ number_format((float) $record->paye_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->advance_deduction > 0)
            <tr>
                <td>Advance</td>
                <td class="text-end">Ksh {{ number_format((float) $record->advance_deduction, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->custom_deductions_total > 0)
            <tr>
                <td>Custom Deductions</td>
                <td class="text-end">Ksh {{ number_format((float) $record->custom_deductions_total, 2) }}</td>
            </tr>
            @endif
            @if((float) $record->other_deductions > 0)
            <tr>
                <td>Other Deductions</td>
                <td class="text-end">Ksh {{ number_format((float) $record->other_deductions, 2) }}</td>
            </tr>
            @endif
            <tr class="border-top">
                <td><strong>Total Deductions</strong></td>
                <td class="text-end"><strong>Ksh {{ number_format((float) $record->total_deductions, 2) }}</strong></td>
            </tr>
        </table>
    </div>
</div>

<hr>

<div class="d-flex justify-content-between align-items-center">
    <div class="text-muted small">Generated {{ now()->format('d M Y H:i') }}</div>
    <h4 class="mb-0">Net Pay: <span class="text-primary">Ksh {{ number_format((float) $record->net_salary, 2) }}</span></h4>
</div>

@if($record->notes)
    <hr>
    <div>
        <h6>Notes</h6>
        <p class="text-muted mb-0">{{ $record->notes }}</p>
    </div>
@endif
