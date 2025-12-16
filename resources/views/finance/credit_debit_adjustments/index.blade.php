@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-arrow-left-right"></i> Credit / Debit Adjustments
                </h3>
                <a href="{{ route('finance.journals.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Adjustment
                </a>
            </div>
        </div>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.journals.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students ?? [] as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->full_name }} ({{ $student->admission_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="credit" {{ request('type') == 'credit' ? 'selected' : '' }}>Credit</option>
                        <option value="debit" {{ request('type') == 'debit' ? 'selected' : '' }}>Debit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="{{ request('year', now()->year) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        @for($i=1;$i<=3;$i++)
                            <option value="{{ $i }}" {{ request('term') == $i ? 'selected' : '' }}>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('finance.journals.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Credit Notes -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-arrow-down-circle"></i> Credit Notes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
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
                            <td colspan="9" class="text-center py-4 text-muted">No credit notes found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($creditNotes) && $creditNotes->hasPages())
        <div class="card-footer">
            {{ $creditNotes->links() }}
        </div>
        @endif
    </div>

    <!-- Debit Notes -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="bi bi-arrow-up-circle"></i> Debit Notes</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
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
                            <td colspan="9" class="text-center py-4 text-muted">No debit notes found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($debitNotes) && $debitNotes->hasPages())
        <div class="card-footer">
            {{ $debitNotes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
