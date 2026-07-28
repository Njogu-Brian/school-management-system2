@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Student Credit / Debit Notes',
        'icon' => 'bi bi-journal-text',
        'subtitle' => 'View credit and debit notes for a student by term and votehead',
        'actions' => '<a href="' . route('finance.journals.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> All Adjustments</a>'
    ])

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
      <div class="finance-card-header d-flex align-items-center gap-2">
        <i class="bi bi-search"></i>
        <span>Select Student</span>
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
            <button type="button" id="viewNotesBtn" class="btn btn-finance btn-finance-primary"
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

<script>
function viewStudentNotes() {
  const studentId = document.getElementById('selectedStudentId').value;
  if (studentId) {
    window.location.href = '{{ route('finance.student-credit-debit-notes.show', ':id') }}'.replace(':id', studentId);
  }
}
</script>
@endsection
