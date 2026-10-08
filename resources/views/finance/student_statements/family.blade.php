@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Family Fee Statement',
        'icon' => 'bi bi-people',
        'subtitle' => ($family->guardian_name ?: 'Family #' . $family->id) . ' - ' . $students->count() . ' student(s)',
        'actions' => '<a href="' . route('finance.student-statements.family.export', ['family' => $family->id, 'year' => $year, 'term' => $term]) . '" target="_blank" class="btn btn-finance btn-finance-primary"><i class="bi bi-file-pdf"></i> Export PDF</a><a href="' . route('finance.student-statements.family.print', ['family' => $family->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-outline" onclick="window.open(\'' . route('finance.student-statements.family.print', ['family' => $family->id, 'year' => $year, 'term' => $term]) . '\', \'FamilyStatementWindow\', \'width=900,height=950,scrollbars=yes,resizable=yes\'); return false;"><i class="bi bi-printer"></i> Print</a><button type="button" class="btn btn-finance btn-finance-outline" data-url="' . e($publicStatementUrl ?? '') . '" onclick="var u=this.getAttribute(\'data-url\'); if(u) { navigator.clipboard.writeText(u); var orig=this.innerHTML; this.innerHTML=\'<i class=&quot;bi bi-check&quot;></i> Copied!\'; var s=this; setTimeout(function(){s.innerHTML=orig}, 2000); }"><i class="bi bi-link-45deg"></i> Copy Public Link</button><button type="button" class="btn btn-finance btn-finance-secondary" onclick="openSendDocument(\'family_statement\', [' . $family->id . '], {message:\'Your family fee statement is ready. Please find the link below.\', params:{year:\'' . $year . '\', term:\'' . ($term ?? '') . '\'}})"><i class="bi bi-send"></i> Send</button>'
    ])

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('finance.student-statements.family.show', $family) }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Academic Year</label>
                <select name="year" class="finance-form-select">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ (int) $year === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select">
                    <option value="">All Terms</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ (string) $term === (string) $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">&nbsp;</label>
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                    <i class="bi bi-filter"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    @include('finance.partials.financial-notes', [
        'familyId' => $family->id,
        'title' => 'Family fee notes',
        'scope' => 'family',
    ])

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="finance-stat-card border-primary finance-animate">
                <h6 class="text-muted mb-2">Total Invoiced</h6>
                <h4 class="mb-0">Ksh {{ number_format($totalCharges, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-success finance-animate">
                <h6 class="text-muted mb-2">Total Payments</h6>
                <h4 class="mb-0">Ksh {{ number_format($totalPayments, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-info finance-animate">
                <h6 class="text-muted mb-2">Total Discounts</h6>
                <h4 class="mb-0">Ksh {{ number_format($totalDiscounts, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card {{ $finalBalance > 0 ? 'border-danger' : 'border-success' }} finance-animate">
                <h6 class="text-muted mb-2">Current Balance</h6>
                <h4 class="mb-0" style="color: {{ $finalBalance > 0 ? '#dc3545' : '#10b981' }};">Ksh {{ number_format($finalBalance, 2) }}</h4>
            </div>
        </div>
        @if(abs((float) $balanceBroughtForward) > 0.009)
            <div class="col-md-3">
                <div class="finance-stat-card border-warning finance-animate">
                    <h6 class="text-muted mb-2">{{ $balanceBroughtForward >= 0 ? 'Balance B/F' : 'Overpayment B/F' }}</h6>
                    <h4 class="mb-0">Ksh {{ number_format(abs((float) $balanceBroughtForward), 2) }}</h4>
                </div>
            </div>
        @endif
    </div>

    {{-- Per-student invoice / payment summary --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-people"></i> <span>Per-Student Summary (Invoices &amp; Payments)</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class</th>
                            <th class="text-end">Invoiced</th>
                            <th class="text-end">Payments</th>
                            <th class="text-end">Discounts</th>
                            <th class="text-end">Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($studentSummaries ?? collect()) as $summary)
                            @php $student = $summary['student']; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $student->full_name }}</div>
                                    <small class="text-muted">{{ $student->admission_number }}</small>
                                </td>
                                <td>{{ optional($student->classroom)->name ?? 'No class' }}</td>
                                <td class="text-end">Ksh {{ number_format($summary['total_charges'], 2) }}</td>
                                <td class="text-end text-success">Ksh {{ number_format($summary['total_payments'], 2) }}</td>
                                <td class="text-end">Ksh {{ number_format($summary['total_discounts'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color: {{ $summary['final_balance'] > 0 ? '#dc3545' : '#10b981' }};">
                                    Ksh {{ number_format($summary['final_balance'], 2) }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('finance.student-statements.show', ['student' => $student->id, 'year' => $year, 'term' => $term]) }}" class="btn btn-sm btn-outline-primary">
                                        Statement
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No students found for this family.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Family totals:</th>
                            <th class="text-end">Ksh {{ number_format($totalCharges, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalPayments, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalDiscounts, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($finalBalance, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Invoices / charges for all students --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center justify-content-between gap-2">
            <span><i class="bi bi-receipt-cutoff"></i> Family Invoices / Charges</span>
            <span class="badge bg-primary">{{ ($invoiceTransactions ?? collect())->count() }} lines</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Votehead</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th class="text-end">Amount (Ksh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($invoiceTransactions ?? collect()) as $transaction)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $transaction['student_name'] ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $transaction['admission_number'] ?? '' }}</small>
                                </td>
                                <td><span class="badge bg-danger-subtle text-danger">{{ $transaction['type'] ?? 'Charge' }}</span></td>
                                <td>{{ $transaction['votehead'] ?? 'N/A' }}</td>
                                <td style="white-space: pre-wrap;">{{ $transaction['narration'] ?? $transaction['description'] ?? 'N/A' }}</td>
                                <td><code>{{ $transaction['reference'] ?? 'N/A' }}</code></td>
                                <td class="text-end fw-semibold">Ksh {{ number_format($transaction['debit'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No invoices/charges found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Payments for all students --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center justify-content-between gap-2">
            <span><i class="bi bi-cash-coin"></i> Family Payments &amp; Credits</span>
            <span class="badge bg-success">{{ ($paymentTransactions ?? collect())->count() }} lines</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Votehead</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th class="text-end">Amount (Ksh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($paymentTransactions ?? collect()) as $transaction)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $transaction['student_name'] ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $transaction['admission_number'] ?? '' }}</small>
                                </td>
                                <td><span class="badge bg-success-subtle text-success">{{ $transaction['type'] ?? 'Payment' }}</span></td>
                                <td>{{ $transaction['votehead'] ?? 'N/A' }}</td>
                                <td style="white-space: pre-wrap;">{{ $transaction['narration'] ?? $transaction['description'] ?? 'N/A' }}</td>
                                <td><code>{{ $transaction['reference'] ?? 'N/A' }}</code></td>
                                <td class="text-end fw-semibold text-success">Ksh {{ number_format($transaction['credit'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No payments/credits found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Combined running ledger --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-list-ul"></i> <span>Family Transaction History (All Students)</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Votehead</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th class="text-end">Debit (Ksh)</th>
                            <th class="text-end">Credit (Ksh)</th>
                            <th class="text-end">Balance (Ksh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                        @endphp
                        @forelse($detailedTransactions as $transaction)
                            @php
                                if (array_key_exists('balance', $transaction) && ($transaction['kind'] ?? null)) {
                                    $runningBalance = (float) $transaction['balance'];
                                } else {
                                    $runningBalance += (($transaction['debit'] ?? 0) - ($transaction['credit'] ?? 0));
                                }
                                $typeLower = strtolower((string) ($transaction['type'] ?? ''));
                                $badgeClass = str_contains($typeLower, 'payment') || str_contains($typeLower, 'discount') || str_contains($typeLower, 'credit')
                                    ? 'bg-success'
                                    : (str_contains($typeLower, 'invoice') || str_contains($typeLower, 'charge') || str_contains($typeLower, 'debit')
                                        ? 'bg-danger'
                                        : 'bg-secondary');
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $transaction['student_name'] ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $transaction['admission_number'] ?? '' }}</small>
                                </td>
                                <td><span class="badge {{ $badgeClass }}">{{ $transaction['type'] ?? 'Entry' }}</span></td>
                                <td>{{ $transaction['votehead'] ?? 'N/A' }}</td>
                                <td style="white-space: pre-wrap;">{{ $transaction['narration'] ?? $transaction['description'] ?? 'N/A' }}</td>
                                <td><code>{{ $transaction['reference'] ?? 'N/A' }}</code></td>
                                <td class="text-end">{{ ($transaction['debit'] ?? 0) > 0 ? 'Ksh ' . number_format($transaction['debit'], 2) : '—' }}</td>
                                <td class="text-end">{{ ($transaction['credit'] ?? 0) > 0 ? 'Ksh ' . number_format($transaction['credit'], 2) : '—' }}</td>
                                <td class="text-end"><strong>Ksh {{ number_format($runningBalance, 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="finance-empty-state">
                                        <i class="bi bi-inbox finance-empty-state-icon"></i>
                                        <h4>No transactions found</h4>
                                        <p>No family transactions were found for the selected period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format($totalDebit, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($totalCredit, 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($finalBalance, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @include('communication.partials.document-send-modal')
@endsection
