@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb">HR & Payroll / Payslips</div>
            <h1 class="mb-1">Staff Payslips</h1>
            <p class="text-muted mb-0">Historical payslips for this staff member.</p>
        </div>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Period</th>
                                <th>Gross</th>
                                <th>Deductions</th>
                                <th>Net</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td>{{ $record->payrollPeriod->period_name ?? '—' }}</td>
                                    <td>Ksh {{ number_format((float) $record->gross_salary, 2) }}</td>
                                    <td>Ksh {{ number_format((float) $record->total_deductions, 2) }}</td>
                                    <td><strong>Ksh {{ number_format((float) $record->net_salary, 2) }}</strong></td>
                                    <td>{{ ucfirst($record->status) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('hr.payroll.records.payslip', $record->id) }}" class="btn btn-sm btn-ghost-strong">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No payslips found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($records->hasPages())
                <div class="card-footer">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
