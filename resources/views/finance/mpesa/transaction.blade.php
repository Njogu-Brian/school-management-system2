@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Transaction Details',
        'icon' => 'bi bi-receipt',
        'subtitle' => 'View transaction information and status',
        'actions' => '<a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Transaction Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Transaction ID</label>
                            <div class="fw-semibold"><code>{{ $transaction->transaction_id ?? 'N/A' }}</code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Reference</label>
                            <div class="fw-semibold">{{ $transaction->reference ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Amount</label>
                            <div class="fw-bold fs-5 text-success">KES {{ number_format($transaction->amount, 2) }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Status</label>
                            <div>
                                @if($transaction->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($transaction->status === 'processing' || $transaction->status === 'pending')
                                    <span class="badge bg-warning">Processing</span>
                                @elseif($transaction->status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($transaction->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Phone Number</label>
                            <div>{{ $transaction->phone_number ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">M-PESA Receipt</label>
                            <div>
                                @if($transaction->mpesa_receipt_number)
                                    <code class="text-success">{{ $transaction->mpesa_receipt_number }}</code>
                                @else
                                    <span class="text-muted">Not available</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Created At</label>
                            <div>{{ $transaction->created_at ? $transaction->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Paid At</label>
                            <div>
                                @if($transaction->paid_at)
                                    {{ $transaction->paid_at->format('d M Y, h:i A') }}
                                @else
                                    <span class="text-muted">Not paid yet</span>
                                @endif
                            </div>
                        </div>
                        @if($transaction->failure_reason)
                        <div class="col-12">
                            <label class="text-muted small">Failure Reason</label>
                            <div class="alert alert-danger mb-0">{{ $transaction->failure_reason }}</div>
                        </div>
                        @endif
                        @if($transaction->admin_notes)
                        <div class="col-12">
                            <label class="text-muted small">Admin Notes</label>
                            <div class="alert alert-info mb-0">{{ $transaction->admin_notes }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($transaction->gateway_response)
            <div class="finance-card finance-animate mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-code-square me-2"></i>Gateway Response</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($transaction->gateway_response, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
            @endif

            @if($transaction->webhook_data)
            <div class="finance-card finance-animate mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-webhook me-2"></i>Webhook Data</h5>
                </div>
                <div class="card-body">
                    <pre class="bg-light p-3 rounded"><code>{{ json_encode($transaction->webhook_data, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="finance-card finance-animate">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Student Information</h5>
                </div>
                <div class="card-body">
                    @if($transaction->student)
                        <div class="mb-3">
                            <label class="text-muted small">Student Name</label>
                            <div class="fw-semibold">
                                <a href="{{ route('students.show', $transaction->student) }}">
                                    {{ $transaction->student->full_name }}
                                </a>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">Admission Number</label>
                            <div>{{ $transaction->student->admission_number }}</div>
                        </div>
                        @if($transaction->student->classroom)
                        <div class="mb-3">
                            <label class="text-muted small">Class</label>
                            <div>{{ $transaction->student->classroom->name }}</div>
                        </div>
                        @endif
                    @else
                        <div class="text-muted">No student associated</div>
                    @endif
                </div>
            </div>

            @if($transaction->invoice)
            <div class="finance-card finance-animate mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Invoice</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Invoice Number</label>
                        <div class="fw-semibold">
                            <a href="{{ route('finance.invoices.show', $transaction->invoice) }}">
                                {{ $transaction->invoice->invoice_number }}
                            </a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Invoice Amount</label>
                        <div>KES {{ number_format($transaction->invoice->total_amount, 2) }}</div>
                    </div>
                </div>
            </div>
            @endif

            @if($transaction->paymentLink)
            <div class="finance-card finance-animate mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Payment Link</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Link Token</label>
                        <div><code>{{ $transaction->paymentLink->token }}</code></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Status</label>
                        <div>
                            @if($transaction->paymentLink->isActive())
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($transaction->status === 'processing' || $transaction->status === 'pending')
            <div class="finance-card finance-animate mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('finance.mpesa.transaction.query', $transaction) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                            <i class="bi bi-search me-2"></i>Query Status
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
