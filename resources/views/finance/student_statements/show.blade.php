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
    <div class="row">
        <div class="col-md-3">
            <div class="finance-stat-card border-primary finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Charges</h6>
                        <h3 class="mb-0">Ksh {{ number_format($totalCharges, 2) }}</h3>
                    </div>
                    <i class="bi bi-arrow-up-circle fs-1 text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-success finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Payments</h6>
                        <h3 class="mb-0">Ksh {{ number_format($totalPayments, 2) }}</h3>
                    </div>
                    <i class="bi bi-cash-stack fs-1 text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-info finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Discounts</h6>
                        <h3 class="mb-0">Ksh {{ number_format($totalDiscounts, 2) }}</h3>
                    </div>
                    <i class="bi bi-percent fs-1 text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card {{ $balance > 0 ? 'border-danger' : 'border-success' }} finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Balance</h6>
                        <h3 class="mb-0">Ksh {{ number_format($balance, 2) }}</h3>
                    </div>
                    <i class="bi bi-wallet2 fs-1 {{ $balance > 0 ? 'text-danger' : 'text-success' }}"></i>
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
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                            $allTransactions = collect();
                            
                            // Add invoices
                            foreach ($invoices as $invoice) {
                                $allTransactions->push([
                                    'date' => $invoice->created_at,
                                    'type' => 'Invoice',
                                    'description' => 'Invoice #' . $invoice->invoice_number . ' - ' . ($invoice->term->name ?? 'Term') . ' ' . $year,
                                    'reference' => $invoice->invoice_number,
                                    'debit' => $invoice->total,
                                    'credit' => 0,
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
                                ]);
                            }
                            
                            // Sort by date
                            $allTransactions = $allTransactions->sortBy('date');
                        @endphp
                        
                        @forelse($allTransactions as $transaction)
                            @php
                                $runningBalance += $transaction['debit'] - $transaction['credit'];
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
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
                            <th class="text-end"><strong>Ksh {{ number_format($balance, 2) }}</strong></th>
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

