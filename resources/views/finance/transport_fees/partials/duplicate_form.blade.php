<div class="finance-card transport-sidebar-card shadow-sm rounded-4 border-0">
  <div class="finance-card-header">
    <i class="bi bi-copy"></i>
    <span>Duplicate Transport Fees</span>
  </div>
  <div class="finance-card-body p-4">
    <p class="transport-desc text-muted small mb-3">Copy transport fees from one term to another. Select a class or specific students; leave empty for entire school.</p>
    <form method="POST" action="{{ route('finance.transport-fees.duplicate') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-6">
          <label class="finance-form-label">Source</label>
          <div class="row g-2">
            <div class="col-6">
              <input type="number" name="source_year" class="finance-form-control" value="{{ $year }}" required placeholder="Year">
            </div>
            <div class="col-6">
              <select name="source_term" class="finance-form-select" required>
                @foreach([1,2,3] as $t)
                  <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <label class="finance-form-label">Target</label>
          <div class="row g-2">
            <div class="col-6">
              <input type="number" name="target_year" class="finance-form-control" required placeholder="Year">
            </div>
            <div class="col-6">
              <select name="target_term" class="finance-form-select" required>
                @foreach([1,2,3] as $t)
                  <option value="{{ $t }}">Term {{ $t }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <label class="finance-form-label">Classroom (optional)</label>
          <select name="classroom_id" class="finance-form-select">
            <option value="">Entire school</option>
            @foreach($classrooms ?? [] as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12">
          <label class="finance-form-label">Or specific students</label>
          <div id="transport-dup-selected" class="d-flex flex-wrap gap-1 mb-2"></div>
          @include('partials.student_live_search', [
            'hiddenInputId' => 'transport_dup_student_id',
            'displayInputId' => 'transport_dup_student_display',
            'resultsId' => 'transport_dup_student_results',
            'placeholder' => 'Search & add students',
            'hiddenInputName' => 'transport_dup_student_id_hidden',
          ])
          <small class="text-muted">Leave classroom and students empty for entire school. Add students to limit scope.</small>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-finance btn-finance-primary w-100">
            <i class="bi bi-copy me-2"></i>Duplicate
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('transport-dup-selected');
  const displayInput = document.getElementById('transport_dup_student_display');
  const hiddenInput = document.getElementById('transport_dup_student_id');
  if (!container) return;
  function addStudent(stu) {
    if (container.querySelector('input[value="'+stu.id+'"]')) return;
    const span = document.createElement('span');
    span.className = 'badge bg-primary d-inline-flex align-items-center gap-1';
    span.innerHTML = (stu.full_name || '') + ' (' + (stu.admission_number || '') + ') ' +
      '<input type="hidden" name="student_ids[]" value="'+stu.id+'">' +
      '<button type="button" class="btn-close btn-close-white btn-close-sm" onclick="this.parentElement.remove()"></button>';
    container.appendChild(span);
    if (displayInput) displayInput.value = '';
    if (hiddenInput) hiddenInput.value = '';
  }
  window.addEventListener('student-selected', function(e) {
    if (e.detail && e.detail.id) addStudent(e.detail);
  });
});
</script>
@endpush
