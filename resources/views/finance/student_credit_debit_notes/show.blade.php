@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Student Credit / Debit Notes',
        'icon' => 'bi bi-journal-text',
        'subtitle' => $student->full_name . ' (' . $student->admission_number . ')',
        'actions' => '<a href="' . route('finance.student-credit-debit-notes.index') . '" class="btn btn-finance btn-finance-outline me-2"><i class="bi bi-search"></i> Find Student</a>'
            . '<a href="' . route('finance.student-statements.show', ['student' => $student->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-file-text"></i> Statement</a>'
    ])

    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <form method="GET" action="{{ route('finance.student-credit-debit-notes.show', $student) }}" class="row g-3" id="notesFilterForm">
        <div class="col-md-4">
          <label class="finance-form-label">Academic Year</label>
          <select name="year" id="yearSelect" class="finance-form-select">
            @foreach($years as $y)
              <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="finance-form-label">Term</label>
          <select name="term" id="termSelect" class="finance-form-select">
            <option value="">All Terms</option>
            @foreach($terms as $t)
              <option value="{{ $t->id }}" @selected((string) $term === (string) $t->id)>{{ $t->name }}</option>
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
      <div class="finance-card-body p-4">
        <div class="row g-3">
          <div class="col-md-3"><strong>Class:</strong> {{ optional($student->classroom)->name ?? 'N/A' }}</div>
          <div class="col-md-3"><strong>Stream:</strong> {{ optional($student->stream)->name ?? 'N/A' }}</div>
          <div class="col-md-3"><strong>Period:</strong> {{ $year }} — {{ $termLabel }}</div>
          <div class="col-md-3"><strong>Notes:</strong> {{ $creditNotes->count() + $debitNotes->count() }}</div>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="finance-stat-card border-success finance-animate">
          <h6 class="text-muted mb-2">Total Credits</h6>
          <h4 class="mb-0 text-success">Ksh {{ number_format($totalCredits, 2) }}</h4>
          <small class="text-muted">Reduces invoice amounts</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="finance-stat-card border-danger finance-animate">
          <h6 class="text-muted mb-2">Total Debits</h6>
          <h4 class="mb-0 text-danger">Ksh {{ number_format($totalDebits, 2) }}</h4>
          <small class="text-muted">Increases invoice amounts</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="finance-stat-card border-primary finance-animate">
          <h6 class="text-muted mb-2">Net Adjustment</h6>
          <h4 class="mb-0">Ksh {{ number_format($netAdjustment, 2) }}</h4>
          <small class="text-muted">Debits minus credits</small>
        </div>
      </div>
    </div>

    @forelse($voteheadGroups as $group)
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <span><i class="bi bi-tag me-2"></i>{{ $group['votehead_name'] }}</span>
          <div class="d-flex gap-3 small">
            <span class="text-success">Credits: Ksh {{ number_format($group['credit_total'], 2) }}</span>
            <span class="text-danger">Debits: Ksh {{ number_format($group['debit_total'], 2) }}</span>
            <span><strong>Net: Ksh {{ number_format($group['net_adjustment'], 2) }}</strong></span>
          </div>
        </div>
        <div class="finance-table-wrapper">
          <div class="table-responsive">
            <table class="finance-table mb-0">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Note #</th>
                  <th>Invoice</th>
                  <th class="text-end">Amount</th>
                  <th>Reason</th>
                  <th>Issued</th>
                  <th>By</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @foreach($group['notes'] as $row)
                  @php $note = $row['note']; @endphp
                  <tr>
                    <td>
                      @if($row['type'] === 'credit')
                        <span class="badge bg-success">Credit</span>
                      @else
                        <span class="badge bg-danger">Debit</span>
                      @endif
                    </td>
                    <td>
                      <strong>
                        {{ $row['type'] === 'credit' ? $note->credit_note_number : $note->debit_note_number }}
                      </strong>
                    </td>
                    <td>{{ $note->invoice->invoice_number ?? '—' }}</td>
                    <td class="text-end">
                      <strong class="{{ $row['type'] === 'credit' ? 'text-success' : 'text-danger' }}">
                        Ksh {{ number_format($note->amount, 2) }}
                      </strong>
                    </td>
                    <td>{{ $note->reason }}</td>
                    <td>{{ $note->issued_at ? \Carbon\Carbon::parse($note->issued_at)->format('d M Y') : '—' }}</td>
                    <td>{{ $note->issuedBy->name ?? '—' }}</td>
                    <td class="text-nowrap">
                      @if($note->invoice_id)
                        <a href="{{ route('finance.invoices.show', $note->invoice_id) }}" class="btn btn-sm btn-outline-primary" title="View invoice">
                          <i class="bi bi-eye"></i>
                        </a>
                      @endif
                      @if($row['type'] === 'credit')
                        <form action="{{ route('finance.credit-notes.reverse', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this credit note?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse">
                            <i class="bi bi-arrow-counterclockwise"></i>
                          </button>
                        </form>
                      @else
                        <form action="{{ route('finance.debit-notes.reverse', $note) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this debit note?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse">
                            <i class="bi bi-arrow-counterclockwise"></i>
                          </button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @empty
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-empty-state p-5">
          <div class="finance-empty-state-icon"><i class="bi bi-journal-x"></i></div>
          <h4>No credit or debit notes found</h4>
          <p class="text-muted mb-0">Try another year or term for this student.</p>
        </div>
      </div>
    @endforelse
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const yearSelect = document.getElementById('yearSelect');
  const termSelect = document.getElementById('termSelect');
  if (!yearSelect || !termSelect) return;

  yearSelect.addEventListener('change', function () {
    const year = yearSelect.value;
    fetch(`{{ route('finance.student-credit-debit-notes.show', $student) }}?year=${year}&get_terms=1`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(data => {
        termSelect.innerHTML = '<option value="">All Terms</option>';
        (data.terms || []).forEach(function (term) {
          const opt = document.createElement('option');
          opt.value = term.id;
          opt.textContent = term.name;
          termSelect.appendChild(opt);
        });
      });
  });
});
</script>
@endpush
@endsection
