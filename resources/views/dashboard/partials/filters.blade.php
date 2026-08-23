@php
  $selectedYearId = (int) ($filters['year_id'] ?? $defaultYearId ?? 0);
  $selectedTermId = (int) ($filters['term_id'] ?? $defaultTermId ?? 0);
  $isFinance = ($role ?? '') === 'finance';
@endphp
<form method="GET" id="dashboard-filters" class="dash-card card card-body mb-3 dash-filters" data-no-year-term-filter>
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <div>
      <div class="fw-semibold">Period</div>
      <div class="dash-muted small mb-0">
        Term-based: invoiced, collected, outstanding, exams.
        Today-based: attendance today, payments today, trips, staff on leave.
      </div>
    </div>
    @if(request()->hasAny(['year_id', 'term_id', 'from', 'to', 'classroom_id', 'stream_id', 'status']))
      <a href="{{ url()->current() }}" class="btn btn-sm dash-btn-ghost">Current term</a>
    @endif
  </div>
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_year_id">Academic Year</label>
      <select name="year_id" id="dashboard_year_id" class="form-select">
        @foreach($years as $y)
          <option value="{{ $y->id }}" data-is-active="{{ !empty($y->is_active) ? '1' : '0' }}" @selected($selectedYearId === (int) $y->id)>{{ $y->name ?? $y->year }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_term_id">Term</label>
      <select name="term_id" id="dashboard_term_id" class="form-select">
        @foreach($terms as $t)
          <option value="{{ $t->id }}"
                  data-academic-year-id="{{ $t->academic_year_id }}"
                  data-opening-date="{{ $t->opening_date?->toDateString() }}"
                  data-closing-date="{{ $t->closing_date?->toDateString() }}"
                  data-is-current="{{ !empty($t->is_current) ? '1' : '0' }}"
                  @selected($selectedTermId === (int) $t->id)>{{ $t->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_from">From</label>
      <input type="date" name="from" id="dashboard_from" class="form-control" value="{{ $filters['from'] }}">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_to">To</label>
      <input type="date" name="to" id="dashboard_to" class="form-control" value="{{ $filters['to'] }}">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_classroom_id">Class</label>
      <select name="classroom_id" id="dashboard_classroom_id" class="form-select">
        <option value="">All</option>
        @foreach($classrooms as $c)
          <option value="{{ $c->id }}" @selected((int) ($filters['classroom_id'] ?? 0) === (int) $c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_stream_id">Stream</label>
      <select name="stream_id" id="dashboard_stream_id" class="form-select">
        <option value="">All</option>
        @foreach($streams as $s)
          <option value="{{ $s->id }}" @selected((int) ($filters['stream_id'] ?? 0) === (int) $s->id)>{{ $s->name }}</option>
        @endforeach
      </select>
    </div>
    @if($isFinance)
    <div class="col-6 col-md-3">
      <label class="form-label" for="dashboard_status">Status</label>
      <select name="status" id="dashboard_status" class="form-select">
        <option value="">All outstanding</option>
        <option value="outstanding" @selected(($filters['status'] ?? '') === 'outstanding')>Outstanding (not overdue)</option>
        <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>Overdue</option>
      </select>
    </div>
    @endif
    <div class="col-12 col-md-3">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Apply Filters</button>
    </div>
  </div>
</form>
@push('scripts')
<script>
(function () {
  const form = document.getElementById('dashboard-filters');
  if (!form) return;
  const yearSelect = form.querySelector('#dashboard_year_id');
  const termSelect = form.querySelector('#dashboard_term_id');
  const fromInput = form.querySelector('#dashboard_from');
  const toInput = form.querySelector('#dashboard_to');
  if (!yearSelect || !termSelect || !fromInput || !toInput) return;
  function todayStr() {
    const d = new Date();
    const pad = function (n) { return String(n).padStart(2, '0'); };
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }
  function visibleTermOptions() {
    return Array.from(termSelect.options).filter(function (opt) { return opt.value && !opt.hidden && !opt.disabled; });
  }
  function filterTermsForYear(yearId, preferredTermId) {
    let preferredVisible = false;
    Array.from(termSelect.options).forEach(function (opt) {
      if (!opt.value) { opt.hidden = false; return; }
      const show = String(opt.getAttribute('data-academic-year-id')) === String(yearId);
      opt.hidden = !show;
      opt.disabled = !show;
      opt.style.display = show ? '' : 'none';
      if (show && preferredTermId && String(opt.value) === String(preferredTermId)) preferredVisible = true;
    });
    if (preferredVisible) { termSelect.value = String(preferredTermId); return; }
    const visible = visibleTermOptions();
    const today = todayStr();
    const current = visible.find(function (opt) { return opt.getAttribute('data-is-current') === '1'; });
    const containingToday = visible.find(function (opt) {
      const open = opt.getAttribute('data-opening-date');
      const close = opt.getAttribute('data-closing-date');
      return open && close && today >= open && today <= close;
    });
    const pick = current || containingToday || visible[visible.length - 1] || visible[0];
    termSelect.value = pick ? pick.value : '';
  }
  function applyTermDates() {
    const opt = termSelect.selectedOptions[0];
    if (!opt || !opt.value) return;
    const open = opt.getAttribute('data-opening-date');
    const close = opt.getAttribute('data-closing-date');
    if (!open || !close) return;
    const today = todayStr();
    fromInput.value = open;
    toInput.value = (today >= open && today <= close) ? today : close;
  }
  yearSelect.addEventListener('change', function () { filterTermsForYear(yearSelect.value, null); applyTermDates(); });
  termSelect.addEventListener('change', applyTermDates);
  filterTermsForYear(yearSelect.value, termSelect.value);
})();
</script>
@endpush
