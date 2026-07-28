@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Student Credit / Debit Notes',
        'icon' => 'bi bi-journal-text',
        'subtitle' => 'View notes for one student or export the entire school by term',
        'actions' => '<a href="' . route('finance.journals.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> All Adjustments</a>'
    ])

    @includeIf('finance.invoices.partials.alerts')

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <div class="finance-card-header d-flex align-items-center gap-2">
        <i class="bi bi-building"></i>
        <span>Export Entire School</span>
      </div>
      <div class="finance-card-body p-4">
        <p class="text-muted small mb-3">
          Download an Excel workbook for all students: one sheet with every credit/debit note,
          and a second sheet summarising totals by student and votehead.
        </p>
        <form method="GET" action="{{ route('finance.student-credit-debit-notes.export') }}" class="row g-3" id="schoolExportForm">
          <div class="col-md-4">
            <label class="finance-form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="year" id="exportYearSelect" class="finance-form-select" required>
              @foreach($years as $y)
                <option value="{{ $y }}" @selected(old('year', $defaultYear) == $y)>{{ $y }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="finance-form-label">Term <span class="text-danger">*</span></label>
            <select name="term" id="exportTermSelect" class="finance-form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected(old('term', $defaultTerm) == $t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="finance-form-label">&nbsp;</label>
            <button type="submit" class="btn btn-finance btn-finance-primary w-100">
              <i class="bi bi-file-earmark-spreadsheet"></i> Export School (Excel)
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-header d-flex align-items-center gap-2">
        <i class="bi bi-search"></i>
        <span>View One Student</span>
      </div>
      <div class="finance-card-body p-4">
        <form class="row g-3" onsubmit="return false;">
          <div class="col-md-12">
            <label class="finance-form-label">Student <span class="text-danger">*</span></label>
            @include('partials.student_live_search', [
                'hiddenInputId' => 'selectedStudentId',
                'displayInputId' => 'studentLiveSearch',
                'resultsId' => 'studentLiveResults',
                'enableButtonId' => 'viewNotesBtn',
                'initialLabel' => request('student_id') ? optional(\App\Models\Student::find(request('student_id')))->search_display : ''
            ])
          </div>
          <div class="col-md-12">
            <button type="button" id="viewNotesBtn" class="btn btn-finance btn-finance-outline"
                    {{ request('student_id') ? '' : 'disabled' }}
                    onclick="viewStudentNotes()">
              <i class="bi bi-eye"></i> View Notes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
function viewStudentNotes() {
  const studentId = document.getElementById('selectedStudentId').value;
  if (studentId) {
    window.location.href = '{{ route('finance.student-credit-debit-notes.show', ':id') }}'.replace(':id', studentId);
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const yearSelect = document.getElementById('exportYearSelect');
  const termSelect = document.getElementById('exportTermSelect');
  if (!yearSelect || !termSelect) return;

  yearSelect.addEventListener('change', function () {
    fetch(`{{ route('finance.student-credit-debit-notes.terms') }}?year=${yearSelect.value}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(r => r.json())
      .then(data => {
        termSelect.innerHTML = '';
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
