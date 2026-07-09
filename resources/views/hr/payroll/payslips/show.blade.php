@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('hr.payroll.partials.styles')
@endpush

@section('content')
<div class="settings-page payroll-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payslip</div>
                <h1 class="mb-1">Payslip</h1>
                <p class="text-muted mb-0">
                    {{ $record->payrollPeriod->period_name ?? '—' }}
                    @if($record->payslip_number)
                        · {{ $record->payslip_number }}
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('hr.payroll.records.show', $record->id) }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <a href="{{ route('hr.payroll.records.payslip.download', $record->id) }}" class="btn btn-settings-primary">
                    <i class="bi bi-download"></i> Download PDF
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                @include('hr.payroll.payslips._body', ['record' => $record, 'forPdf' => false])
            </div>
        </div>
    </div>
</div>
@endsection
