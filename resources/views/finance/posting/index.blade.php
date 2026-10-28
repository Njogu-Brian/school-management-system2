@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Post Pending Fees',
      'icon' => 'bi bi-arrow-right-circle',
      'subtitle' => 'Move pending fee structures to active invoices'
  ])

  @includeIf('finance.invoices.partials.alerts')

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-funnel"></i> <span>Select Criteria</span>
    </div>
    <div class="finance-card-body p-4">
      <div class="alert alert-light border mb-3">
        <small class="text-muted">
          <i class="bi bi-info-circle me-1"></i>
          <strong>Tip:</strong> Leaving Class, Stream, and Student as "All" will post for the <strong>entire school</strong>. To post for fewer students, select a Class, Stream, or search and add specific students.
        </small>
      </div>
      <div class="alert alert-secondary mb-3">
        <strong>Future years:</strong> if you post for a year after the current academic year (e.g. 2027 while still in 2026),
        invoices use each child’s <em>mapped next class</em> fee structure (including the final year, e.g. Grade 8 → Grade 9).
        Only children with no next class mapped (leaving school) are blocked.
      </div>
      <form method="POST" action="{{ route('finance.posting.preview') }}" class="row g-3">
        @csrf

        <div class="col-md-6 col-lg-2">
          <label class="finance-form-label">Academic Year</label>
          <select name="year" id="postingYearSelect" class="finance-form-select" required>
            @forelse($academicYears ?? [] as $ay)
              <option value="{{ $ay->year }}"
                @selected((int) request('year', $defaultYear ?? $currentYear ?? now()->year) === (int) $ay->year)>
                {{ $ay->year }}@if(!$ay->is_active) (inactive)@endif
              </option>
            @empty
              <option value="{{ $defaultYear ?? now()->year }}">{{ $defaultYear ?? now()->year }}</option>
            @endforelse
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Term</label>
          <select name="term" id="postingTermSelect" class="finance-form-select" required>
            {{-- Populated by JS from terms that exist for the selected year --}}
          </select>
          <small id="postingTermHint" class="text-muted d-none">
            No terms for this year yet. Add them under
            <a href="{{ route('settings.academic.index') }}">Settings → Academic</a>.
          </small>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Votehead (optional)</label>
          <select name="votehead_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($voteheads as $vh)
              <option value="{{ $vh->id }}">{{ $vh->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Class (optional)</label>
          <select name="class_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($classrooms as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Student Category (optional)</label>
          <select name="student_category_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-2">
          <label class="finance-form-label">Stream (optional)</label>
          <select name="stream_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($streams as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12">
          <label class="finance-form-label">Or select specific student(s)</label>
          <div class="d-flex gap-2 flex-wrap align-items-start">
            @include('partials.student_live_search', [
              'hiddenInputId' => 'posting_student_id',
              'hiddenInputName' => 'student_id',
              'displayInputId' => 'posting_student_display',
              'resultsId' => 'posting_student_results',
              'placeholder' => 'Search & add students by name or admission #',
              'initialLabel' => '',
              'initialStudentId' => '',
            ])
            <div class="flex-grow-1">
              <small class="text-muted d-block">Leave empty to use class/stream filters. Search and add students to post for specific students only.</small>
              <div id="posting-selected-students" class="mt-2 d-flex flex-wrap gap-1">
                @foreach((array) request('student_ids', []) as $sid)
                  @php $s = \App\Models\Student::find($sid); @endphp
                  @if($s)
                    <span class="badge bg-primary d-inline-flex align-items-center gap-1">
                      {{ $s->full_name }} ({{ $s->admission_number }})
                      <input type="hidden" name="student_ids[]" value="{{ $s->id }}">
                      <button type="button" class="btn-close btn-close-white btn-close-sm" aria-label="Remove" onclick="this.parentElement.remove()"></button>
                    </span>
                  @endif
                @endforeach
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-search"></i> Preview
          </button>
        </div>
      </form>
    </div>
  </div>

  @if(isset($runs) && $runs->count() > 0)
  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header secondary d-flex align-items-center gap-2">
      <i class="bi bi-clock-history"></i> <span>Previous Posting Runs</span>
    </div>
    <div class="finance-table-wrapper">
      <div class="table-responsive px-3 pb-3">
        <table class="finance-table align-middle">
          <thead>
        <tr>
          <th>Run ID</th>
          <th>Academic Year</th>
          <th>Term</th>
          <th>Status</th>
          <th>Items Posted</th>
          <th>Posted By</th>
          <th>Posted At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($runs as $run)
        <tr>
          <td>#{{ $run->id }}</td>
          <td>{{ $run->academicYear->year ?? 'N/A' }}</td>
          <td>{{ $run->term->name ?? 'N/A' }}</td>
          <td>
            <span class="finance-badge badge-{{ $run->status === 'completed' ? 'approved' : ($run->status === 'reversed' ? 'rejected' : 'pending') }}">
              {{ ucfirst($run->status) }}
            </span>
          </td>
          <td>{{ $run->items_posted_count ?? 0 }}</td>
          <td>{{ $run->postedBy->name ?? 'System' }}</td>
          <td>{{ $run->posted_at ? $run->posted_at->format('d M Y, H:i') : 'N/A' }}</td>
          <td>
            <div class="finance-action-buttons">
              <a href="{{ route('finance.posting.show', $run) }}" class="btn btn-sm btn-info">
                <i class="bi bi-eye"></i> View
              </a>
              @if($run->canBeReversed())
              <form action="{{ route('finance.posting.reverse', $run) }}" method="POST" class="d-inline" 
                    onsubmit="return confirm('Are you sure you want to reverse this posting run? This will remove all invoice items created by this run. If items have payment allocations, reversal will be blocked.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger">
                  <i class="bi bi-arrow-counterclockwise"></i> Reverse
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
          </tbody>
        </table>
      </div>
      <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        {{ $runs->links() }}
      </div>
    </div>
  </div>
  @endif

  @push('scripts')
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const termsByYear = @json($termsByYear ?? new \stdClass());
    const preferredTerm = {{ (int) ($defaultTerm ?? $currentTermNumber ?? 1) }};
    const yearSelect = document.getElementById('postingYearSelect');
    const termSelect = document.getElementById('postingTermSelect');
    const termHint = document.getElementById('postingTermHint');
    const previewBtn = document.querySelector('form[action="{{ route('finance.posting.preview') }}"] button[type="submit"]');

    function refreshTerms() {
      if (!yearSelect || !termSelect) return;
      const year = String(yearSelect.value || '');
      const terms = termsByYear[year] || [];
      const previous = termSelect.value;
      termSelect.innerHTML = '';

      if (!terms.length) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No terms for ' + year;
        termSelect.appendChild(opt);
        termSelect.disabled = true;
        termSelect.required = false;
        if (termHint) termHint.classList.remove('d-none');
        if (previewBtn) previewBtn.disabled = true;
        return;
      }

      termSelect.disabled = false;
      termSelect.required = true;
      if (termHint) termHint.classList.add('d-none');
      if (previewBtn) previewBtn.disabled = false;

      terms.forEach(function (t) {
        const opt = document.createElement('option');
        opt.value = String(t.number);
        opt.textContent = t.name;
        termSelect.appendChild(opt);
      });

      const preferred = previous || String(preferredTerm);
      if ([...termSelect.options].some(o => o.value === preferred)) {
        termSelect.value = preferred;
      } else {
        termSelect.selectedIndex = 0;
      }
    }

    if (yearSelect) {
      yearSelect.addEventListener('change', refreshTerms);
      refreshTerms();
    }

    const container = document.getElementById('posting-selected-students');
    const displayInput = document.getElementById('posting_student_display');
    const hiddenInput = document.getElementById('posting_student_id');
    if (!container) return;

    function addStudentToList(stu) {
      if (container.querySelector(`input[value="${stu.id}"]`)) return; // already added
      const span = document.createElement('span');
      span.className = 'badge bg-primary d-inline-flex align-items-center gap-1';
      span.innerHTML = `${escapeHtml(stu.full_name)} (${escapeHtml(stu.admission_number || '')}) ` +
        `<input type="hidden" name="student_ids[]" value="${stu.id}">` +
        `<button type="button" class="btn-close btn-close-white btn-close-sm" aria-label="Remove" onclick="this.parentElement.remove()"></button>`;
      container.appendChild(span);
      displayInput.value = '';
      hiddenInput.value = '';
    }
    function escapeHtml(s) {
      if (s == null) return '';
      const div = document.createElement('div');
      div.textContent = s;
      return div.innerHTML;
    }
    window.addEventListener('student-selected', function(e) {
      if (e.detail && e.detail.id) addStudentToList(e.detail);
    });
  });
  </script>
  @endpush
@endsection
