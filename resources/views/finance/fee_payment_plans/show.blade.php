@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @php
        $publicPlanUrl = url('/payment-plan/' . $feePaymentPlan->hashed_id);
        $payNowUrl = null;
        $installmentDisplay = 'KES ' . number_format((float) $feePaymentPlan->installment_amount, 2);
        if ($feePaymentPlan->relationLoaded('installments') && $feePaymentPlan->installments->isNotEmpty()) {
            $distinctAmounts = $feePaymentPlan->installments
                ->map(fn ($inst) => round((float) $inst->amount, 2))
                ->unique()
                ->values();
            if ($distinctAmounts->count() > 1) {
                $installmentDisplay = 'Variable (see schedule)';
            } elseif ($distinctAmounts->count() === 1) {
                $installmentDisplay = 'KES ' . number_format((float) $distinctAmounts->first(), 2);
            }
        }
        if ($feePaymentPlan->student && $feePaymentPlan->student->family_id) {
            $link = \App\Models\PaymentLink::getOrCreateFamilyLink((int) $feePaymentPlan->student->family_id, auth()->id(), 'payment_plan_show');
            $payNowUrl = $link->getPaymentUrl();
        }
    @endphp

    <div class="finance-card finance-animate mb-3 p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <div class="text-muted small">Finance</div>
                <h1 class="h4 mb-1">Payment Plan Details</h1>
                <div class="small text-muted">View the plan schedule, covered invoices, and share payment links with parents.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $publicPlanUrl }}" target="_blank" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-box-arrow-up-right"></i> Public view
                </a>
                @if($payNowUrl)
                    <a href="{{ $payNowUrl }}" target="_blank" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-credit-card"></i> Pay now
                    </a>
                @endif
                <a href="{{ route('finance.fee-payment-plans.print', $feePaymentPlan) }}" target="_blank" class="btn btn-finance btn-finance-outline" title="Opens a printable page with letterhead and signature lines">
                    <i class="bi bi-printer"></i> Print agreement
                </a>
                <a href="{{ route('finance.fee-payment-plans.download-pdf', $feePaymentPlan) }}" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-file-pdf"></i> Download PDF
                </a>
                <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-finance btn-finance-outline">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="mb-0">Plan Information</h5>
                </div>
                <div class="finance-card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="text-muted small">Student</div>
                            <div class="fw-semibold">
                                <a href="{{ route('students.show', $feePaymentPlan->student) }}">{{ $feePaymentPlan->student->full_name }}</a>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('finance.accountant-dashboard.student-history', $feePaymentPlan->student) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-clock-history"></i> Payment History
                                </a>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Status</div>
                            <span class="badge bg-{{ $feePaymentPlan->status == 'active' ? 'success' : ($feePaymentPlan->status == 'completed' ? 'info' : 'secondary') }}">
                                {{ ucfirst($feePaymentPlan->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">Total amount</div>
                                <div class="h5 mb-0">KES {{ number_format($feePaymentPlan->total_amount, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">Installments</div>
                                <div class="h5 mb-0">{{ $feePaymentPlan->installment_count }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">Installment amount</div>
                                <div class="h6 mb-0">{{ $installmentDisplay }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded border bg-light">
                                <div class="text-muted small">Period</div>
                                <div class="small mb-0">
                                    {{ $feePaymentPlan->start_date->format('M d, Y') }} → {{ $feePaymentPlan->end_date->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $covered = $feePaymentPlan->invoices ?? collect();
                $covered = $covered->filter(fn ($i) => $i && $i->student)->groupBy(fn ($i) => $i->student->id);
            @endphp
            @if($covered->count() > 0)
                <div class="finance-card finance-animate mb-4">
                    <div class="finance-card-header">
                        <h5 class="mb-0">Covered Invoices (Family)</h5>
                    </div>
                    <div class="finance-card-body">
                        @foreach($covered as $studentId => $invoices)
                            @php
                                $stu = $invoices->first()->student;
                                $studentName = $stu->full_name ?? trim(($stu->first_name ?? '') . ' ' . ($stu->last_name ?? ''));
                                $studentTotal = (float) $invoices->sum(fn ($inv) => (float) ($inv->total ?? 0));
                                $studentBalance = (float) $invoices->sum(fn ($inv) => (float) ($inv->balance ?? 0));
                            @endphp
                            <div class="mb-3">
                                <div class="fw-semibold">{{ $studentName }}</div>
                                <div class="small text-muted">
                                    Total invoiced: KES {{ number_format($studentTotal, 2) }} · Outstanding: KES {{ number_format($studentBalance, 2) }}
                                </div>
                                <div class="small mt-1">
                                    @foreach($invoices as $inv)
                                        <span class="badge bg-light text-dark border me-1 mb-1">
                                            {{ $inv->invoice_number ?? ('Invoice #' . $inv->id) }}:
                                            total KES {{ number_format((float) ($inv->total ?? 0), 2) }},
                                            balance KES {{ number_format((float) ($inv->balance ?? 0), 2) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-6">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Installments</h5>
                    <form action="{{ route('finance.fee-payment-plans.update-status', $feePaymentPlan) }}" method="POST" class="d-inline">
                        @csrf
                        <select name="status" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                            <option value="active" {{ $feePaymentPlan->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="completed" {{ $feePaymentPlan->status == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $feePaymentPlan->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </form>
                </div>
                <div class="finance-card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($feePaymentPlan->installments as $installment)
                                    <tr>
                                        <td>{{ $installment->installment_number }}</td>
                                        <td>KES {{ number_format($installment->amount, 2) }}</td>
                                        <td>{{ $installment->due_date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $installment->status == 'paid' ? 'success' : ($installment->status == 'overdue' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($installment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection

