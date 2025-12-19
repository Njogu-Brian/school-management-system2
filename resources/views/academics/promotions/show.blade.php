@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Promote Students: {{ $classroom->name }}</h1>
        <p class="text-muted mb-0">
          @if($classroom->is_alumni)
            Mark students as Alumni
          @elseif($classroom->nextClass)
            Promote to <strong>{{ $classroom->nextClass->name }}</strong>
          @else
            <span class="text-danger">No next class mapped. Set next class first.</span>
          @endif
        </p>
      </div>
      <a href="{{ route('academics.promotions.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @if(!$classroom->nextClass && !$classroom->is_alumni)
      <div class="alert alert-warning alert-soft border-0">
        <i class="bi bi-exclamation-triangle"></i> This class does not have a next class mapped. <a href="{{ route('academics.classrooms.edit', $classroom) }}">Set next class</a> before promoting.
      </div>
    @endif

    @php
      $alreadyPromoted = false;
      if ($currentYear) {
        $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
          ->where('academic_year_id', $currentYear->id)
          ->where('promotion_status', 'promoted')
          ->exists();
      }
    @endphp

    @if($alreadyPromoted)
      <div class="alert alert-warning alert-soft border-0">
        <i class="bi bi-info-circle"></i> Already promoted this academic year ({{ $currentYear->year }}). One promotion per year.
      </div>
    @endif

    <form action="{{ route('academics.promotions.promote', $classroom) }}" method="POST" id="promotionForm" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
              <option value="">-- Select Year --</option>
              @foreach(\App\Models\AcademicYear::orderBy('year', 'desc')->get() as $year)
                <option value="{{ $year->id }}" @selected($currentYear && $currentYear->id == $year->id)>{{ $year->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Term <span class="text-danger">*</span></label>
            <select name="term_id" class="form-select" required>
              <option value="">-- Select Term --</option>
              @foreach(\App\Models\Term::orderBy('name')->get() as $term)
                <option value="{{ $term->id }}" @selected($currentTerm && $currentTerm->id == $term->id)>{{ $term->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Promotion Date <span class="text-danger">*</span></label>
            <input type="date" name="promotion_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
        </div>

        <div class="settings-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Select Students to Promote</h6>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-ghost-strong" onclick="selectAll()">Select All</button>
              <button type="button" class="btn btn-sm btn-ghost-strong" onclick="deselectAll()">Deselect All</button>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width:50px;"><input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()"></th>
                    <th>Admission #</th>
                    <th>Name</th>
                    <th>Stream</th>
                    <th>Current Class</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($students as $student)
                    <tr>
                      <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox"></td>
                      <td class="fw-semibold">{{ $student->admission_number }}</td>
                      <td>{{ $student->full_name }}</td>
                      <td>
                        @if($student->stream)
                          <span class="pill-badge pill-info">{{ $student->stream->name }}</span>
                        @else
                          <span class="text-muted">No stream</span>
                        @endif
                      </td>
                      <td>{{ $classroom->name }}</td>
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No students in this class.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Notes (Optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes about this promotion..."></textarea>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="{{ route('academics.promotions.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <div class="d-flex gap-2 flex-wrap">
          @if($students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni) && !$alreadyPromoted)
            <button type="button" class="btn btn-ghost-strong" onclick="promoteAll()">
              <i class="bi bi-arrow-up-circle-fill"></i> Promote All Students
            </button>
          @endif
          <button type="submit" class="btn btn-settings-primary" @if((!$classroom->nextClass && !$classroom->is_alumni) || $alreadyPromoted) disabled @endif>
            <i class="bi bi-arrow-up-circle"></i> {{ $classroom->is_alumni ? 'Mark as Alumni' : 'Promote Selected' }}
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function toggleAll() {
  const selectAll = document.getElementById('selectAllCheckbox').checked;
  document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = selectAll);
}
function selectAll() {
  document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
  const master = document.getElementById('selectAllCheckbox');
  if (master) master.checked = true;
}
function deselectAll() {
  document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
  const master = document.getElementById('selectAllCheckbox');
  if (master) master.checked = false;
}
function promoteAll() {
  selectAll();
  const total = document.querySelectorAll('.student-checkbox').length;
  const action = @if($classroom->is_alumni) 'mark as alumni' @else 'promote' @endif;
  if (confirm(`Are you sure you want to ${action} ALL ${total} student(s)?`)) {
    document.getElementById('promotionForm').submit();
  }
}

document.getElementById('promotionForm').addEventListener('submit', function(e) {
  const checked = document.querySelectorAll('.student-checkbox:checked').length;
  if (checked === 0) {
    e.preventDefault();
    alert('Please select at least one student to promote.');
    return false;
  }
  const action = @if($classroom->is_alumni) 'mark as alumni' @else 'promote' @endif;
  if (!confirm(`Are you sure you want to ${action} ${checked} student(s)?`)) {
    e.preventDefault();
    return false;
  }
});
</script>
@endpush
@endsection
