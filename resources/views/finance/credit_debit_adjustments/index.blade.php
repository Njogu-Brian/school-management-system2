@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Credit / Debit Adjustments',
        'icon' => 'bi bi-arrow-left-right',
        'subtitle' => 'Manage credit and debit note adjustments',
        'actions' => '<a href="' . route('finance.journals.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> New Adjustment</a>'
    ])

    @includeIf('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="finance-filter-card finance-animate">
        <form method="GET" action="{{ route('finance.journals.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="finance-form-label">Student</label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'student_id',
                    'displayInputId' => 'studentFilterSearchCDA',
                    'resultsId' => 'studentFilterResultsCDA',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => request('student_id') ? (optional(\App\Models\Student::find(request('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(request('student_id')))->admission_number . ')') : ''
                ])
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Type</label>
                <select name="type" class="finance-form-select">
                    <option value="">All Types</option>
                    <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Credit</option>
                    <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Debit</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Year</label>
                <input type="number" name="year" class="finance-form-control" value="{{ request('year') }}" placeholder="All Years">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select">
                    <option value="">All Terms</option>
                    @for($i=1;$i<=3;$i++)
                        <option value="{{ $i }}" {{ request('term') == $i ? 'selected' : '' }}>Term {{ $i }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('finance.journals.index') }}" class="btn btn-finance btn-finance-outline">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Credit Notes -->
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header success">
            <i class="bi bi-arrow-down-circle me-2"></i> Credit Notes
        </div>
        <div class="finance-table-wrapper">
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Credit Note #</th>
                            <th>Student</th>
                            <th>Invoice</th>
                            <th>Votehead</th>
                            <th class="text-end">Amount</th>
                            <th>Reason</th>
                            <th>Issued Date</th>
                            <th>Issued By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditNotes ?? [] as $note)
                        <tr>
                            <td><strong>{{ $note->credit_note_number }}</strong></td>
                            <td>{{ $note->invoice->student->full_name ?? 'N/A' }}</td>
                            <td>{{ $note->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $note->invoiceItem->votehead->name ?? 'N/A' }}</td>
                            <td class="text-end text-success"><strong>Ksh {{ number_format($note->amount, 2) }}</strong></td>
                            <td>{{ $note->reason }}</td>
                            <td>{{ $note->issued_at ? \Carbon\Carbon::parse($note->issued_at)->format('d M Y') : '—' }}</td>
                            <td>{{ $note->issuedBy->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('finance.invoices.show', $note->invoice_id) }}" class="btn btn-sm btn-outline-primary" title="View Invoice">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('finance.credit-notes.reverse', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reverse this credit note? This will remove it from the invoice.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Reverse">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-arrow-down-circle"></i>
                                    </div>
                                    <h4>No credit notes found</h4>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($creditNotes) && $creditNotes->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $creditNotes->links() }}
        </div>
        @endif
    </div>

    <!-- Debit Notes -->
    <div class="finance-card finance-animate">
        <div class="finance-card-header danger">
            <i class="bi bi-arrow-up-circle me-2"></i> Debit Notes
        </div>
        <div class="finance-table-wrapper">
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Debit Note #</th>
                            <th>Student</th>
                            <th>Invoice</th>
                            <th>Votehead</th>
                            <th class="text-end">Amount</th>
                            <th>Reason</th>
                            <th>Issued Date</th>
                            <th>Issued By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debitNotes ?? [] as $note)
                        <tr>
                            <td><strong>{{ $note->debit_note_number }}</strong></td>
                            <td>{{ $note->invoice->student->full_name ?? 'N/A' }}</td>
                            <td>{{ $note->invoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $note->invoiceItem->votehead->name ?? 'N/A' }}</td>
                            <td class="text-end text-danger"><strong>Ksh {{ number_format($note->amount, 2) }}</strong></td>
                            <td>{{ $note->reason }}</td>
                            <td>{{ $note->issued_at ? \Carbon\Carbon::parse($note->issued_at)->format('d M Y') : '—' }}</td>
                            <td>{{ $note->issuedBy->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('finance.invoices.show', $note->invoice_id) }}" class="btn btn-sm btn-outline-primary" title="View Invoice">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form action="{{ route('finance.debit-notes.reverse', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reverse this debit note? This will remove it from the invoice.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Reverse">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-arrow-up-circle"></i>
                                    </div>
                                    <h4>No debit notes found</h4>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($debitNotes) && $debitNotes->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $debitNotes->links() }}
        </div>
        @endif
    </div>

    {{-- Import Section --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="finance-card shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex align-items-center gap-2">
                    <i class="bi bi-upload"></i>
                    <span>Import Credit/Debit Notes</span>
                </div>
                <div class="finance-card-body p-4">
                    <p class="text-muted">Upload an Excel file with columns: Name, Admission Number, CR (Credit), DR (Debit).</p>
                    <form method="POST" action="{{ route('finance.credit-debit-notes.import.preview') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="finance-form-label">File (.xlsx/.csv)</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                            <div class="col-md-2">
                                <label class="finance-form-label">Year</label>
                                <input type="number" name="year" class="finance-form-control" value="{{ $currentYear ?? now()->year }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="finance-form-label">Term</label>
                                <select name="term" class="finance-form-select" required>
                                    @foreach([1,2,3] as $t)
                                        <option value="{{ $t }}" @selected(($currentTermNumber ?? 1) == $t)>Term {{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="finance-form-label">Votehead</label>
                                <select name="votehead_id" class="finance-form-select" required>
                                    <option value="">Select Votehead</option>
                                    @foreach($voteheads ?? [] as $votehead)
                                        <option value="{{ $votehead->id }}">{{ $votehead->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-finance btn-finance-primary w-100">
                                    <i class="bi bi-eye"></i> Preview &amp; apply
                                </button>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-12">
                                <a class="btn btn-outline-secondary" href="{{ route('finance.credit-debit-notes.import.template') }}">
                                    <i class="bi bi-download"></i> Download Template
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection
