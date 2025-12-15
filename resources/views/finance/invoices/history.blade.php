@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-clock-history"></i> Invoice History - {{ $invoice->invoice_number }}
                </h3>
                <a href="{{ route('finance.invoices.show', $invoice) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Invoice
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Invoice Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Invoice Number:</dt>
                        <dd class="col-sm-9"><strong>{{ $invoice->invoice_number }}</strong></dd>

                        <dt class="col-sm-3">Student:</dt>
                        <dd class="col-sm-9">
                            {{ $invoice->student->first_name ?? 'N/A' }} {{ $invoice->student->last_name ?? '' }}
                            <br><small class="text-muted">{{ $invoice->student->admission_number ?? 'N/A' }}</small>
                        </dd>

                        <dt class="col-sm-3">Total Amount:</dt>
                        <dd class="col-sm-9"><strong>Ksh {{ number_format($invoice->total, 2) }}</strong></dd>

                        <dt class="col-sm-3">Paid Amount:</dt>
                        <dd class="col-sm-9">
                            <span class="text-success">Ksh {{ number_format($invoice->paid_amount ?? 0, 2) }}</span>
                        </dd>

                        <dt class="col-sm-3">Balance:</dt>
                        <dd class="col-sm-9">
                            <span class="text-{{ ($invoice->balance ?? $invoice->total) > 0 ? 'danger' : 'success' }}">
                                Ksh {{ number_format($invoice->balance ?? $invoice->total, 2) }}
                            </span>
                        </dd>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'danger') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Credit/Debit Notes -->
            @if($invoice->creditNotes->isNotEmpty() || $invoice->debitNotes->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Credit & Debit Notes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Number</th>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Issued Date</th>
                                    <th>Issued By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->creditNotes as $note)
                                <tr>
                                    <td><span class="badge bg-success">Credit Note</span></td>
                                    <td>{{ $note->credit_note_number }}</td>
                                    <td class="text-end">Ksh {{ number_format($note->amount, 2) }}</td>
                                    <td>{{ $note->reason }}</td>
                                    <td>{{ $note->issued_at ? \Carbon\Carbon::parse($note->issued_at)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $note->creator->name ?? 'System' }}</td>
                                </tr>
                                @endforeach
                                
                                @foreach($invoice->debitNotes as $note)
                                <tr>
                                    <td><span class="badge bg-danger">Debit Note</span></td>
                                    <td>{{ $note->debit_note_number }}</td>
                                    <td class="text-end">Ksh {{ number_format($note->amount, 2) }}</td>
                                    <td>{{ $note->reason }}</td>
                                    <td>{{ $note->issued_at ? \Carbon\Carbon::parse($note->issued_at)->format('d M Y') : 'N/A' }}</td>
                                    <td>{{ $note->creator->name ?? 'System' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Payment History -->
            @if($invoice->payments->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Receipt Number</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('finance.payments.show', $payment) }}">
                                            {{ $payment->receipt_number ?? $payment->transaction_code }}
                                        </a>
                                    </td>
                                    <td class="text-end">Ksh {{ number_format($payment->amount, 2) }}</td>
                                    <td>{{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}</td>
                                    <td>{{ $payment->reference ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Audit Logs (if available) -->
            @php
                $auditLogs = \App\Models\AuditLog::where('auditable_type', \App\Models\Invoice::class)
                    ->where('auditable_id', $invoice->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            @endphp
            
            @if($auditLogs->isNotEmpty())
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Audit Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at->format('d M Y, H:i') }}</td>
                                    <td><span class="badge bg-info">{{ ucfirst($log->event) }}</span></td>
                                    <td>{{ $log->user->name ?? 'System' }}</td>
                                    <td>
                                        <small class="text-muted">
                                            @if($log->old_values)
                                                <strong>Before:</strong> {{ json_encode($log->old_values) }}<br>
                                            @endif
                                            @if($log->new_values)
                                                <strong>After:</strong> {{ json_encode($log->new_values) }}
                                            @endif
                                        </small>
                                    </td>
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.payments.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}" class="btn btn-primary">
                            <i class="bi bi-cash-stack"></i> Record Payment
                        </a>
                        <a href="{{ route('finance.invoices.print_single', $invoice) }}" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-printer"></i> Print Invoice
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

