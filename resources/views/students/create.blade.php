@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Create' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Student Admission</h1>
        <p class="text-muted mb-0">Add a new learner with profile and placement details.</p>
      </div>
      <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    @include('students.partials.alerts')

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="settings-card" id="studentAdmissionForm">
      @include('students.partials.form', [
        'mode' => 'create',
        'countryCodes' => $countryCodes ?? [],
        // controller should pass these:
        // 'classrooms'=>$classrooms, 'streams'=>$streams, 'categories'=>$categories, 'trips'=>$trips, 'dropOffPoints'=>$dropOffPoints
      ])
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const wrap = document.getElementById('duplicate-confirm-wrap');
  const list = document.getElementById('duplicate-match-list');
  const message = document.getElementById('duplicate-confirm-message');
  if (!wrap || !list) return;

  const checkUrl = @json(route('students.duplicate-check'));
  const form = document.getElementById('studentAdmissionForm');
  let timer = null;

  const field = (name) => form?.querySelector(`[name="${name}"]`);

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function renderMatches(matches) {
    if (!matches.length) {
      list.innerHTML = '';
      wrap.classList.add('d-none');
      return;
    }
    wrap.classList.remove('d-none');
    list.innerHTML = '<ul class="list-unstyled mb-0">' + matches.map((match) => {
      const extra = match.admission_number
        ? ` (${escapeHtml(match.admission_number)})`
        : (match.application_no ? ` (${escapeHtml(match.application_no)})` : '');
      const badgeClass = match.confidence === 'high' ? 'bg-danger' : 'bg-warning text-dark';
      const open = match.url
        ? `<a href="${escapeHtml(match.url)}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Open</a>`
        : '';
      return `<li class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2 border-bottom">
        <div>
          <div class="fw-semibold">${escapeHtml(match.full_name)}${extra}</div>
          <div class="small text-muted">${escapeHtml(match.source_label || '')}${match.status ? ' · ' + escapeHtml(match.status) : ''}${match.classroom ? ' · ' + escapeHtml(match.classroom) : ''}</div>
          <div class="small"><span class="badge ${badgeClass}">${escapeHtml(match.reason_label || '')}</span></div>
        </div>
        ${open}
      </li>`;
    }).join('') + '</ul>';
  }

  function runCheck() {
    const first = (field('first_name')?.value || '').trim();
    const last = (field('last_name')?.value || '').trim();
    const dob = (field('dob')?.value || '').trim();
    const nemis = (field('nemis_number')?.value || '').trim();
    const knec = (field('knec_assessment_number')?.value || '').trim();
    if ((!first || !last || !dob) && !nemis && !knec) {
      if (!@json(!empty(session('duplicate_matches')))) {
        renderMatches([]);
      }
      return;
    }
    const params = new URLSearchParams({
      first_name: first,
      middle_name: field('middle_name')?.value || '',
      last_name: last,
      dob,
      gender: field('gender')?.value || '',
      nemis_number: nemis,
      knec_assessment_number: knec,
      admission_number: field('admission_number')?.value || '',
    });
    fetch(checkUrl + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.message) message.textContent = data.message;
        renderMatches(data.matches || []);
      })
      .catch(() => {});
  }

  ['first_name', 'middle_name', 'last_name', 'dob', 'gender', 'nemis_number', 'knec_assessment_number', 'admission_number']
    .forEach((name) => {
      const el = field(name);
      if (!el) return;
      el.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(runCheck, 450);
      });
      el.addEventListener('change', () => {
        clearTimeout(timer);
        timer = setTimeout(runCheck, 150);
      });
    });
})();
</script>
@endpush
