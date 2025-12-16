@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Student Fee Statement',
        'icon' => 'bi bi-file-text',
        'subtitle' => $student->full_name . ' (' . $student->admission_number . ')',
        'actions' => '<a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'pdf']) . '" target="_blank" class="btn btn-finance btn-finance-primary"><i class="bi bi-file-pdf"></i> Export PDF</a><a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'csv']) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a><button onclick="window.print()" class="btn btn-finance btn-finance-outline"><i class="bi bi-printer"></i> Print</button>'
    ])

    {{-- Filters --}}
    <div class="finance-filter-card finance-animate">
        <form method="GET" action="{{ route('finance.student-statements.show', $student) }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Academic Year</label>
                <select name="year" class="finance-form-select" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select" onchange="this.form.submit()">
                    <option value="">All Terms</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ $term == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Student Info --}}
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-person-circle me-2"></i> Student Information
        </div>
        <div class="finance-card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Name:</strong> {{ $student->full_name }}
                </div>
                <div class="col-md-3">
                    <strong>Admission Number:</strong> {{ $student->admission_number }}
                </div>
                <div class="col-md-3">
                    <strong>Class:</strong> {{ $student->currentClass->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Year:</strong> {{ $year }} @if($term) | <strong>Term:</strong> {{ $terms->find($term)->name ?? "Term {$term}" }} @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="finance-stat-card border-primary finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Charges</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalCharges, 2) }}</h4>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2rem; color: var(--finance-primary);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-success finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Payments</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalPayments, 2) }}</h4>
                    </div>
                    <i class="bi bi-cash-stack" style="font-size: 2rem; color: var(--finance-success);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-info finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Discounts</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalDiscounts, 2) }}</h4>
                    </div>
                    <i class="bi bi-percent" style="font-size: 2rem; color: var(--finance-info);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card {{ $balance > 0 ? 'border-danger' : 'border-success' }} finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Balance</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700; color: {{ $balance > 0 ? '#dc3545' : '#10b981' }};">Ksh {{ number_format($balance, 2) }}</h4>
                    </div>
                    <i class="bi bi-wallet2" style="font-size: 2rem; color: {{ $balance > 0 ? '#dc3545' : '#10b981' }};"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-list-ul me-2"></i> Transaction History
        </div>
        <div class="finance-card-body">
            <div class="finance-table-wrapper">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                            $allTransactions = collect();
                            $finalBalance = 0; // Will store final balance after all transactions
                            
                            // Add invoices
                            foreach ($invoices as $invoice) {
                                $allTransactions->push([
                                    'date' => $invoice->created_at,
                                    'type' => 'Invoice',
                                    'description' => 'Invoice #' . $invoice->invoice_number . ' - ' . ($invoice->term->name ?? 'Term') . ' ' . $year,
                                    'reference' => $invoice->invoice_number,
                                    'debit' => $invoice->total,
                                    'credit' => 0,
                                    'model_id' => $invoice->id,
                                ]);
                            }
                            
                            // Add payments
                            foreach ($payments as $payment) {
                                $allTransactions->push([
                                    'date' => $payment->payment_date,
                                    'type' => 'Payment',
                                    'description' => 'Payment - ' . ($payment->paymentMethod->name ?? 'N/A'),
                                    'reference' => $payment->receipt_number,
                                    'debit' => 0,
                                    'credit' => $payment->amount,
                                    'model_id' => $payment->id,
                                ]);
                            }
                            
                            // Add discounts
                            foreach ($discounts as $discount) {
                                $allTransactions->push([
                                    'date' => $discount->created_at,
                                    'type' => 'Discount',
                                    'description' => ($discount->discountTemplate->name ?? 'Discount'),
                                    'reference' => 'DIS-' . $discount->id,
                                    'debit' => 0,
                                    'credit' => $discount->value,
                                    'model_id' => $discount->id,
                                ]);
                            }
                            
                            // Add credit notes
                            foreach ($creditNotes as $note) {
                                $allTransactions->push([
                                    'date' => $note->created_at,
                                    'type' => 'Credit Note',
                                    'description' => $note->reason,
                                    'reference' => $note->credit_note_number,
                                    'debit' => 0,
                                    'credit' => $note->amount,
                                    'model_id' => $note->id,
                                ]);
                            }
                            
                            // Add debit notes
                            foreach ($debitNotes as $note) {
                                $allTransactions->push([
                                    'date' => $note->created_at,
                                    'type' => 'Debit Note',
                                    'description' => $note->reason,
                                    'reference' => $note->debit_note_number,
                                    'debit' => $note->amount,
                                    'credit' => 0,
                                    'model_id' => $note->id,
                                ]);
                            }
                            
                            // Sort by date
                            $allTransactions = $allTransactions->sortBy('date');
                        @endphp
                        
                        @forelse($allTransactions as $transaction)
                            @php
                                $runningBalance += $transaction['debit'] - $transaction['credit'];
                                $finalBalance = $runningBalance; // Update final balance
                                $transactionId = $transaction['model_id'] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $transaction['date']->format('d M Y') }}</td>
                                <td>
                                    @if($transaction['type'] == 'Invoice')
                                        <span class="badge bg-primary">Invoice</span>
                                    @elseif($transaction['type'] == 'Payment')
                                        <span class="badge bg-success">Payment</span>
                                    @elseif($transaction['type'] == 'Discount')
                                        <span class="badge bg-info">Discount</span>
                                    @elseif($transaction['type'] == 'Credit Note')
                                        <span class="badge bg-warning">Credit</span>
                                    @elseif($transaction['type'] == 'Debit Note')
                                        <span class="badge bg-danger">Debit</span>
                                    @endif
                                </td>
                                <td>{{ $transaction['description'] }}</td>
                                <td><code>{{ $transaction['reference'] }}</code></td>
                                <td class="text-end">{{ $transaction['debit'] > 0 ? 'Ksh ' . number_format($transaction['debit'], 2) : '—' }}</td>
                                <td class="text-end">{{ $transaction['credit'] > 0 ? 'Ksh ' . number_format($transaction['credit'], 2) : '—' }}</td>
                                <td class="text-end"><strong>Ksh {{ number_format($runningBalance, 2) }}</strong></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($transaction['type'] == 'Invoice' && $transactionId)
                                            <a href="{{ route('finance.invoices.show', $transactionId) }}" class="btn btn-sm btn-outline-primary" title="View Invoice">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @elseif($transaction['type'] == 'Payment' && $transactionId)
                                            <a href="{{ route('finance.payments.show', $transactionId) }}" class="btn btn-sm btn-outline-success" title="View Payment">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @elseif($transaction['type'] == 'Credit Note' && $transactionId)
                                            <form action="{{ route('finance.credit-notes.reverse', $transactionId) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this credit note?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Reverse Credit Note">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @elseif($transaction['type'] == 'Debit Note' && $transactionId)
                                            <form action="{{ route('finance.debit-notes.reverse', $transactionId) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this debit note?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse Debit Note">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="finance-empty-state">
                                        <i class="bi bi-inbox finance-empty-state-icon"></i>
                                        <h4>No transactions found</h4>
                                        <p>No financial transactions recorded for this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format($totalCharges + $totalDebitNotes, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalPayments + $totalDiscounts + $totalCreditNotes, 2) }}</th>
                            <th class="text-end"><strong>Ksh {{ number_format($finalBalance ?? $balance, 2) }}</strong></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .finance-header, .finance-filter-card, .btn, .sidebar, .content { margin-left: 0 !important; }
    .finance-card { page-break-inside: avoid; }
    .finance-stat-card { page-break-inside: avoid; }
}
</style>
@endsection

