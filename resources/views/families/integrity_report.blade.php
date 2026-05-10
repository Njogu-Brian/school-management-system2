@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      .integrity-report .integrity-hero-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .12), rgba(var(--bs-info-rgb), .12));
        color: var(--bs-primary);
      }
      .integrity-report .integrity-subcard {
        border-radius: 0.75rem;
        border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        background: var(--bs-body-bg);
        box-shadow: 0 0.125rem 0.35rem rgba(0, 0, 0, 0.04);
      }
      .integrity-report .integrity-section-title {
        letter-spacing: 0.02em;
      }
      .integrity-report .table-modern thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--bs-secondary-color);
      }
      .integrity-report .gap-pill {
        font-size: 0.7rem;
        font-weight: 600;
      }
    </style>
@endpush

@section('content')
@php
  $ccOpts = $countryCodes ?? [['code' => '+254', 'label' => 'Kenya (+254)']];
@endphp
<div class="settings-page integrity-report">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex gap-3 align-items-start">
        <span class="integrity-hero-icon"><i class="bi bi-shield-exclamation"></i></span>
        <div>
          <div class="crumb"><a href="{{ route('families.index') }}" class="text-reset text-decoration-none">Families</a></div>
          <h1 class="mb-1">Family integrity report</h1>
          <p class="text-muted mb-2 mb-md-0">Find duplicate parent phones/emails, link siblings safely, and patch missing father/mother contacts without leaving this screen.</p>
          <div class="d-flex flex-wrap gap-2">
            <span class="pill-badge pill-secondary">{{ count($duplicatePhoneGroups) }} phone groups</span>
            <span class="pill-badge pill-secondary">{{ count($duplicateEmailGroups) }} email groups</span>
            <span class="pill-badge pill-warning">{{ $missingPhones->total() }} missing-phone students</span>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Families</a>
        <a href="{{ route('families.link') }}" class="btn btn-ghost-strong"><i class="bi bi-link-45deg"></i> Manual link</a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="alert alert-soft border-0 mb-4">
      <div class="d-flex gap-2">
        <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
        <div class="small">
          <strong class="d-block mb-1">How linking works</strong>
          “Link selected as siblings” posts to the same merge flow as <em>Families → Link students</em> (combines families and consolidates duplicate parent rows when contacts match). Pick 2–40 students per submit.
        </div>
      </div>
    </div>

    {{-- Duplicate phones --}}
    <div class="settings-card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="text-primary"><i class="bi bi-telephone-fill fs-5"></i></span>
          <div>
            <h5 class="mb-0 integrity-section-title">Duplicate phone numbers</h5>
            <p class="text-muted small mb-0">Same stored number on multiple parent rows (grouped by column).</p>
          </div>
        </div>
      </div>
      <div class="card-body">
        @forelse($duplicatePhoneGroups as $group)
          <div class="integrity-subcard p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $group['field']) }}</span>
                <code class="small px-2 py-1 rounded bg-light">{{ $group['value'] }}</code>
                <span class="badge rounded-pill bg-secondary">{{ $group['count_parents'] }} parents</span>
                <span class="badge rounded-pill bg-warning text-dark">{{ $group['students']->count() }} students</span>
              </div>
              <span class="small text-muted">{{ $group['family_summary'] }}</span>
            </div>
            @if($group['students']->count() > 40)
              <div class="alert alert-warning py-2 small mb-2 mb-0">More than 40 students — uncheck rows and link in batches (max 40 per submit).</div>
            @endif
            <form method="POST" action="{{ route('families.link.store') }}" class="duplicate-link-form mt-3"
                  onsubmit="return confirm('Link the selected students as siblings?');">
              @csrf
              <div class="table-responsive">
                <table class="table table-sm table-modern align-middle mb-2">
                  <thead class="table-light">
                    <tr>
                      <th style="width:36px;"></th>
                      <th>Admission</th>
                      <th>Student</th>
                      <th>Class</th>
                      <th>Parent row</th>
                      <th class="text-end">Open</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($group['students'] as $stu)
                      <tr>
                        <td>
                          <input class="form-check-input stu-chk" type="checkbox" name="student_ids[]" value="{{ $stu->id }}" checked>
                        </td>
                        <td class="fw-semibold">{{ $stu->admission_number }}</td>
                        <td>{{ $stu->full_name }}</td>
                        <td>{{ $stu->classroom->name ?? '—' }}</td>
                        <td class="small text-muted font-monospace">#{{ $stu->parent_id }}</td>
                        <td class="text-end">
                          @if(Route::has('students.show'))
                            <a href="{{ route('students.show', $stu->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Profile</a>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <button type="submit" class="btn btn-settings-primary btn-sm link-submit">
                <i class="bi bi-link-45deg"></i> Link selected as siblings
              </button>
            </form>
          </div>
        @empty
          <p class="text-muted mb-0">No duplicate phone groups (within limits).</p>
        @endforelse
      </div>
    </div>

    {{-- Duplicate emails --}}
    <div class="settings-card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span class="text-primary"><i class="bi bi-envelope-fill fs-5"></i></span>
        <div>
          <h5 class="mb-0 integrity-section-title">Duplicate email addresses</h5>
          <p class="text-muted small mb-0">Compared case-insensitive after trim.</p>
        </div>
      </div>
      <div class="card-body">
        @forelse($duplicateEmailGroups as $group)
          <div class="integrity-subcard p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $group['field']) }}</span>
                <code class="small px-2 py-1 rounded bg-light">{{ $group['value'] }}</code>
                <span class="badge rounded-pill bg-secondary">{{ $group['count_parents'] }} parents</span>
                <span class="badge rounded-pill bg-warning text-dark">{{ $group['students']->count() }} students</span>
              </div>
              <span class="small text-muted">{{ $group['family_summary'] }}</span>
            </div>
            @if($group['students']->count() > 40)
              <div class="alert alert-warning py-2 small mb-2 mb-0">More than 40 students — uncheck rows and link in batches (max 40 per submit).</div>
            @endif
            <form method="POST" action="{{ route('families.link.store') }}" class="duplicate-link-form mt-3"
                  onsubmit="return confirm('Link the selected students as siblings?');">
              @csrf
              <div class="table-responsive">
                <table class="table table-sm table-modern align-middle mb-2">
                  <thead class="table-light">
                    <tr>
                      <th style="width:36px;"></th>
                      <th>Admission</th>
                      <th>Student</th>
                      <th>Class</th>
                      <th>Parent row</th>
                      <th class="text-end">Open</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($group['students'] as $stu)
                      <tr>
                        <td>
                          <input class="form-check-input stu-chk" type="checkbox" name="student_ids[]" value="{{ $stu->id }}" checked>
                        </td>
                        <td class="fw-semibold">{{ $stu->admission_number }}</td>
                        <td>{{ $stu->full_name }}</td>
                        <td>{{ $stu->classroom->name ?? '—' }}</td>
                        <td class="small text-muted font-monospace">#{{ $stu->parent_id }}</td>
                        <td class="text-end">
                          @if(Route::has('students.show'))
                            <a href="{{ route('students.show', $stu->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Profile</a>
                          @endif
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <button type="submit" class="btn btn-settings-primary btn-sm link-submit">
                <i class="bi bi-link-45deg"></i> Link selected as siblings
              </button>
            </form>
          </div>
        @empty
          <p class="text-muted mb-0">No duplicate email groups (within limits).</p>
        @endforelse
      </div>
    </div>

    {{-- Missing phones --}}
    <div class="settings-card mb-4">
      <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <span class="text-danger"><i class="bi bi-person-exclamation fs-5"></i></span>
          <div>
            <h5 class="mb-0 integrity-section-title">Missing father or mother phone</h5>
            <p class="text-muted small mb-0">Active students only. Use quick add to patch blanks from this page.</p>
          </div>
        </div>
      </div>
      <div class="card-body">
        @if($missingPhones->isEmpty())
          <p class="text-muted mb-0">No matching students.</p>
        @else
          <div class="table-responsive mb-3">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Admission</th>
                  <th>Student</th>
                  <th>Class</th>
                  <th>Gaps</th>
                  <th>Father</th>
                  <th>Mother</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($missingPhones as $stu)
                  @php
                    $p = $stu->parent;
                    $mf = ! filled($p->father_phone ?? null);
                    $mm = ! filled($p->mother_phone ?? null);
                    $payload = [
                      'student_id' => $stu->id,
                      'label' => $stu->admission_number.' — '.$stu->full_name,
                      'father_cc' => $p->father_phone_country_code ?? '+254',
                      'mother_cc' => $p->mother_phone_country_code ?? '+254',
                      'father_empty' => $mf,
                      'mother_empty' => $mm,
                    ];
                  @endphp
                  <tr>
                    <td class="fw-semibold">{{ $stu->admission_number }}</td>
                    <td>{{ $stu->full_name }}</td>
                    <td>{{ $stu->classroom->name ?? '—' }}</td>
                    <td>
                      @if($mf)
                        <span class="badge rounded-pill text-bg-danger gap-pill">Father</span>
                      @endif
                      @if($mm)
                        <span class="badge rounded-pill text-bg-danger gap-pill">Mother</span>
                      @endif
                    </td>
                    <td class="small">{{ filled($p->father_phone ?? null) ? $p->father_phone : '—' }}</td>
                    <td class="small">{{ filled($p->mother_phone ?? null) ? $p->mother_phone : '—' }}</td>
                    <td class="text-end text-nowrap">
                      @if(Route::has('families.integrity-report.quick-parent-phones'))
                        <button type="button"
                                class="btn btn-sm btn-settings-primary quick-contact-open"
                                data-payload="{{ e(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) }}">
                          <i class="bi bi-lightning-charge"></i> Quick add
                        </button>
                      @endif
                      @if(Route::has('students.edit'))
                        <a href="{{ route('students.edit', $stu->id) }}" class="btn btn-sm btn-outline-secondary ms-1" target="_blank" rel="noopener">Full edit</a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          {{ $missingPhones->links() }}
        @endif
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-sliders me-1"></i> Report options</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('families.integrity-report') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label small text-muted">Max duplicate groups per section</label>
            <input type="number" name="dup_limit" class="form-control" min="10" max="100" value="{{ $dupLimit }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">Missing-phone rows per page</label>
            <input type="number" name="missing_per_page" class="form-control" min="10" max="100" value="{{ $missingPerPage }}">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-settings-primary w-100 w-md-auto"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Quick contact modal --}}
@if(Route::has('families.integrity-report.quick-parent-phones'))
<div class="modal fade" id="quickContactModal" tabindex="-1" aria-labelledby="quickContactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title" id="quickContactModalLabel"><i class="bi bi-telephone-plus me-2 text-primary"></i>Quick add contact</h5>
          <p class="text-muted small mb-0" id="quickContactStudentLabel"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('families.integrity-report.quick-parent-phones') }}" id="quickContactForm">
        @csrf
        <input type="hidden" name="student_id" id="quick_contact_student_id" value="">
        <div class="modal-body pt-2">
          <p class="small text-muted">Country code first, then local digits only (same as student admission form). Only <strong>blank</strong> father/mother slots are saved.</p>

          <div class="row g-3" id="quick_father_row">
            <div class="col-12"><span class="fw-semibold small text-uppercase text-muted">Father</span></div>
            <div class="col-md-4">
              <label class="form-label small">Country code</label>
              <select name="father_phone_country_code" id="quick_father_cc" class="form-select">
                @foreach($ccOpts as $cc)
                  <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small">Local phone</label>
              <input type="text" name="father_phone" id="quick_father_phone" class="form-control" placeholder="Digits only" inputmode="numeric" autocomplete="tel-national">
            </div>
          </div>

          <hr class="my-3">

          <div class="row g-3" id="quick_mother_row">
            <div class="col-12"><span class="fw-semibold small text-uppercase text-muted">Mother</span></div>
            <div class="col-md-4">
              <label class="form-label small">Country code</label>
              <select name="mother_phone_country_code" id="quick_mother_cc" class="form-select">
                @foreach($ccOpts as $cc)
                  <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small">Local phone</label>
              <input type="text" name="mother_phone" id="quick_mother_phone" class="form-control" placeholder="Digits only" inputmode="numeric" autocomplete="tel-national">
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-settings-primary"><i class="bi bi-check2-circle"></i> Save contact</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.duplicate-link-form').forEach(function (form) {
  function updateBtn() {
    const n = form.querySelectorAll('.stu-chk:checked').length;
    const btn = form.querySelector('.link-submit');
    if (btn) btn.disabled = n < 2 || n > 40;
  }
  form.querySelectorAll('.stu-chk').forEach(function (cb) {
    cb.addEventListener('change', updateBtn);
  });
  form.addEventListener('submit', function (e) {
    const n = form.querySelectorAll('.stu-chk:checked').length;
    if (n > 40) {
      e.preventDefault();
      alert('Select at most 40 students per link action.');
    }
  });
  updateBtn();
});

(function () {
  const modalEl = document.getElementById('quickContactModal');
  if (!modalEl || typeof bootstrap === 'undefined') return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('quickContactForm');
  const sid = document.getElementById('quick_contact_student_id');
  const lbl = document.getElementById('quickContactStudentLabel');
  const rowF = document.getElementById('quick_father_row');
  const rowM = document.getElementById('quick_mother_row');
  const fCc = document.getElementById('quick_father_cc');
  const fPh = document.getElementById('quick_father_phone');
  const mCc = document.getElementById('quick_mother_cc');
  const mPh = document.getElementById('quick_mother_phone');

  function setSelectValue(sel, code) {
    if (!sel) return;
    const opt = Array.from(sel.options).find(function (o) { return o.value === code; });
    sel.value = opt ? code : (sel.options[0] ? sel.options[0].value : '');
  }

  document.querySelectorAll('.quick-contact-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      let payload = {};
      try {
        payload = JSON.parse(btn.getAttribute('data-payload') || '{}');
      } catch (e) {
        payload = {};
      }
      sid.value = payload.student_id || '';
      lbl.textContent = payload.label || '';

      const showF = !!payload.father_empty;
      const showM = !!payload.mother_empty;
      rowF.style.display = showF ? '' : 'none';
      rowM.style.display = showM ? '' : 'none';

      fPh.value = '';
      mPh.value = '';
      setSelectValue(fCc, payload.father_cc || '+254');
      setSelectValue(mCc, payload.mother_cc || '+254');

      if (!showF) {
        fPh.removeAttribute('name');
        if (fCc) fCc.removeAttribute('name');
      } else {
        fPh.setAttribute('name', 'father_phone');
        if (fCc) fCc.setAttribute('name', 'father_phone_country_code');
      }
      if (!showM) {
        mPh.removeAttribute('name');
        if (mCc) mCc.removeAttribute('name');
      } else {
        mPh.setAttribute('name', 'mother_phone');
        if (mCc) mCc.setAttribute('name', 'mother_phone_country_code');
      }

      modal.show();
    });
  });

  modalEl.addEventListener('hidden.bs.modal', function () {
    if (form) form.reset();
  });
})();
</script>
@endpush
