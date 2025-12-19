@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff Advances</div>
                <h1 class="mb-1">Advance Details</h1>
                <p class="text-muted mb-0">View advance loan information.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="pill-badge pill-info">{{ ucfirst($advance->status) }}</span>
                <a href="{{ route('hr.payroll.advances.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                @if($advance->status === 'pending')
                    <a href="{{ route('hr.payroll.advances.edit', $advance->id) }}" class="btn btn-settings-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Advance Information</h5>
                            <p class="text-muted small mb-0">Amounts, repayment method, and dates.</p>
                        </div>
                        <span class="pill-badge pill-secondary">Ref #{{ $advance->id }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Staff Member</label>
                                <div class="fw-semibold">{{ $advance->staff->name }}</div>
                                <div class="small text-muted">ID: {{ $advance->staff->staff_id }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>
                                @php
                                    $badgeColors = [
                                      'pending' => 'pill-warning',
                                      'approved' => 'pill-info',
                                      'active' => 'pill-success',
                                      'completed' => 'pill-secondary',
                                      'cancelled' => 'pill-danger'
                                    ];
                                    $badge = $badgeColors[$advance->status] ?? 'pill-secondary';
                                @endphp
                                <div>
                                    <span class="pill-badge {{ $badge }}">{{ ucfirst($advance->status) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="text-muted small">Advance Amount</label>
                                <div class="h4 text-primary mb-0">Ksh {{ number_format($advance->amount, 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Amount Repaid</label>
                                <div class="h4 text-success mb-0">Ksh {{ number_format($advance->amount_repaid, 2) }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small">Balance</label>
                                <div class="h4 text-info mb-0">Ksh {{ number_format($advance->balance, 2) }}</div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Advance Date</label>
                                <div>{{ $advance->advance_date->format('F d, Y') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Purpose</label>
                                <div>{{ $advance->purpose ?? '—' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Repayment Method</label>
                                <div>
                                    <span class="pill-badge pill-info">{{ ucfirst(str_replace('_', ' ', $advance->repayment_method)) }}</span>
                                    @if($advance->monthly_deduction_amount)
                                        <div class="small text-muted mt-1">Ksh {{ number_format($advance->monthly_deduction_amount, 2) }} per month</div>
                                    @endif
                                    @if($advance->installment_count)
                                        <div class="small text-muted mt-1">{{ $advance->installment_count }} installments</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Expected Completion</label>
                                <div>{{ $advance->expected_completion_date ? $advance->expected_completion_date->format('F d, Y') : '—' }}</div>
                            </div>
                            @if($advance->completed_date)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Completed Date</label>
                                <div>{{ $advance->completed_date->format('F d, Y') }}</div>
                            </div>
                            @endif
                            @if($advance->approved_by)
                            <div class="col-md-6 mb-3">
                                <label class="text-muted small">Approved By</label>
                                <div>{{ $advance->approvedBy->name ?? '—' }}</div>
                                <div class="small text-muted">{{ $advance->approved_at ? $advance->approved_at->format('M d, Y H:i') : '' }}</div>
                            </div>
                            @endif
                            @if($advance->description)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Description</label>
                                <div>{{ $advance->description }}</div>
                            </div>
                            @endif
                            @if($advance->notes)
                            <div class="col-12 mb-3">
                                <label class="text-muted small">Notes</label>
                                <div class="alert alert-soft border-0 mb-0">{{ nl2br(e($advance->notes)) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if($advance->customDeductions->count() > 0)
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Related Deductions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-modern table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Deduction Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Effective From</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($advance->customDeductions as $deduction)
                                        <tr>
                                            <td>{{ $deduction->deductionType->name }}</td>
                                            <td>Ksh {{ number_format($deduction->amount, 2) }}</td>
                                            <td><span class="pill-badge {{ $deduction->status === 'active' ? 'pill-success' : 'pill-secondary' }}">{{ ucfirst($deduction->status) }}</span></td>
                                            <td>{{ $deduction->effective_from->format('M d, Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                @if($advance->status === 'pending')
                <div class="settings-card mb-3">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.advances.approve', $advance->id) }}" method="POST" onsubmit="return confirm('Approve this advance?')">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-check-circle"></i> Approve Advance
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if($advance->status === 'active' && $advance->balance > 0)
                <div class="settings-card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Record Repayment</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('hr.payroll.advances.repayment', $advance->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Amount (Ksh)</label>
                                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $advance->balance }}" class="form-control" required>
                                <div class="form-text">Maximum: Ksh {{ number_format($advance->balance, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control"></textarea>
                            </div>
                            <button type="submit" class="btn btn-settings-primary w-100">
                                <i class="bi bi-cash"></i> Record Repayment
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Progress</h5>
                        <span class="pill-badge pill-success">{{ number_format($percentage, 1) }}% repaid</span>
                    </div>
                    <div class="card-body">
                        @php
                            $percentage = $advance->amount > 0 ? ($advance->amount_repaid / $advance->amount) * 100 : 0;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Repayment Progress</span>
                                <span>{{ number_format($percentage, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%">{{ number_format($percentage, 1) }}%</div>
                            </div>
                        </div>
                        <div class="small text-muted">
                            <div>Total: Ksh {{ number_format($advance->amount, 2) }}</div>
                            <div>Repaid: Ksh {{ number_format($advance->amount_repaid, 2) }}</div>
                            <div>Remaining: Ksh {{ number_format($advance->balance, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

