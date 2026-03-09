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

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-people"></i> <span>Children Covered</span>
        </div>
        <div class="finance-card-body p-4">
            <div class="row g-3">
                @foreach($students as $student)
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="fw-semibold">{{ $student->full_name }}</div>
                            <div class="text-muted small">{{ $student->admission_number }}</div>
                            <div class="text-muted small">{{ optional($student->classroom)->name ?? 'No class' }}</div>
                            <a href="{{ route('finance.student-statements.show', ['student' => $student->id, 'year' => $year, 'term' => $term]) }}" class="btn btn-sm btn-outline-primary mt-2">Individual Statement</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

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

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-list-ul"></i> <span>Family Transaction History</span>
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
                        @php($runningBalance = 0)
                        @forelse($detailedTransactions as $transaction)
                            @php($runningBalance += (($transaction['debit'] ?? 0) - ($transaction['credit'] ?? 0)))
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $transaction['student_name'] ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $transaction['admission_number'] ?? '' }}</small>
                                </td>
                                <td><span class="badge bg-secondary">{{ $transaction['type'] ?? 'Entry' }}</span></td>
                                <td>{{ $transaction['votehead'] ?? 'N/A' }}</td>
                                <td>{{ $transaction['narration'] ?? $transaction['description'] ?? 'N/A' }}</td>
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
