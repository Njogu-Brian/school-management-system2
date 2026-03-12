<div class="finance-card shadow-sm rounded-4 border-0">
  <div class="finance-card-header d-flex align-items-center gap-2">
    <i class="bi bi-copy"></i>
    <span>Duplicate Optional Fees</span>
  </div>
  <div class="finance-card-body p-4">
    <p class="text-muted small mb-3">Copy optional fees from one term to another. Existing invoices are updated; new ones created when needed. Target must be a future term.</p>
    <form method="POST" action="{{ route('finance.optional_fees.duplicate') }}">
      @csrf
      <div class="row g-3">
        <div class="col-12">
          <label class="finance-form-label">Source (from)</label>
          <select name="source_year_term" class="finance-form-select" required>
            @foreach(($termsByYear ?? collect())->sortKeysDesc() as $yr => $terms)
              @foreach($terms as $t)
                @php $termNum = (int) preg_replace('/[^0-9]/', '', $t->name) ?: 1; @endphp
                <option value="{{ $t->academicYear->year ?? $yr }}|{{ $termNum }}" @selected(($defaultYear ?? '') == ($t->academicYear->year ?? $yr) && ($defaultTerm ?? 1) == $termNum)>
                  {{ $t->academicYear->year ?? $yr }} – {{ $t->name }}
                </option>
              @endforeach
            @endforeach
            @if(($termsByYear ?? collect())->isEmpty())
              <option value="{{ $defaultYear ?? date('Y') }}|{{ $defaultTerm ?? 1 }}">{{ $defaultYear ?? date('Y') }} – Term {{ $defaultTerm ?? 1 }}</option>
            @endif
          </select>
        </div>
        <div class="col-12">
          <label class="finance-form-label">Target (to) – future terms only</label>
          <select name="target_year_term" class="finance-form-select" required>
            <option value="">Select target term</option>
            @foreach($futureTerms ?? [] as $t)
              @php $termNum = (int) preg_replace('/[^0-9]/', '', $t->name) ?: 1; @endphp
              <option value="{{ $t->academicYear->year ?? '' }}|{{ $termNum }}">
                {{ $t->academicYear->year ?? '?' }} – {{ $t->name }}
              </option>
            @endforeach
            @if(($futureTerms ?? collect())->isEmpty())
              <option value="" disabled>No future terms in calendar</option>
            @endif
          </select>
        </div>
        <div class="col-12">
          <label class="finance-form-label">Voteheads (optional – all if empty)</label>
          <select name="votehead_ids[]" class="finance-form-select" multiple size="3">
            @foreach($optionalVoteheads ?? [] as $vh)
              <option value="{{ $vh->id }}">{{ $vh->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12">
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
          <div id="optional-dup-selected" class="d-flex flex-wrap gap-1 mb-2"></div>
          @include('partials.student_live_search', [
            'hiddenInputId' => 'optional_dup_student_id',
            'displayInputId' => 'optional_dup_student_display',
            'resultsId' => 'optional_dup_student_results',
            'placeholder' => 'Search & add students',
            'hiddenInputName' => 'optional_dup_student_id_hidden',
          ])
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-copy"></i> Duplicate
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const container = document.getElementById('optional-dup-selected');
  const displayInput = document.getElementById('optional_dup_student_display');
  const hiddenInput = document.getElementById('optional_dup_student_id');
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
