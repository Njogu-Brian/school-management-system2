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
      .integrity-report .duplicate-actions {
        margin-top: 0.75rem;
      }
      @media (max-width: 767.98px) {
        .integrity-report .settings-shell {
          padding-left: 0.5rem;
          padding-right: 0.5rem;
        }
        .integrity-report .integrity-subcard {
          padding: 0.75rem !important;
        }
        .integrity-report .table-modern {
          font-size: 0.82rem;
        }
        .integrity-report .duplicate-actions .btn {
          width: 100%;
        }
      }
    </style>
@endpush

@section('content')
<div class="settings-page integrity-report">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex gap-3 align-items-start">
        <span class="integrity-hero-icon"><i class="bi bi-shield-exclamation"></i></span>
        <div>
          <div class="crumb"><a href="{{ route('families.index') }}" class="text-reset text-decoration-none">Families</a></div>
          <h1 class="mb-1">Family integrity report</h1>
          <p class="text-muted mb-2 mb-md-0">Find duplicate parent phones/emails and link siblings safely. Missing contacts live on a separate page.</p>
          <div class="d-flex flex-wrap gap-2">
            <span class="pill-badge pill-secondary">{{ count($duplicatePhoneGroups) }} phone groups</span>
            <span class="pill-badge pill-secondary">{{ count($duplicateEmailGroups) }} email groups</span>
            @if(Route::has('families.integrity-report.missing-contacts'))
              <a href="{{ route('families.integrity-report.missing-contacts') }}" class="pill-badge pill-warning text-decoration-none">Missing contacts →</a>
            @endif
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Families</a>
        @if(Route::has('families.integrity-report.missing-contacts'))
          <a href="{{ route('families.integrity-report.missing-contacts') }}" class="btn btn-settings-primary"><i class="bi bi-person-lines-fill"></i> Missing contacts</a>
        @endif
        <a href="{{ route('families.link') }}" class="btn btn-ghost-strong"><i class="bi bi-link-45deg"></i> Manual link</a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="alert alert-soft border-0 mb-4">
      <div class="d-flex gap-2">
        <i class="bi bi-info-circle flex-shrink-0 mt-1"></i>
        <div class="small">
          <strong class="d-block mb-1">How linking works</strong>
          Duplicate sections list <strong>active students only</strong> (archived are hidden). A group appears only when at least <strong>two different parent rows</strong> are still in use among those students — duplicate database rows that no longer have active students attached are hidden. “Link selected as siblings” merges families, then <strong>combines parent rows</strong> when they share the same phone digits or email: the system keeps the richest record, merges missing fields (longer text wins on conflicts), repoints every student to one parent row, and deletes the extras.
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
                <span class="badge rounded-pill bg-secondary">{{ $group['count_parents_db'] }} rows in DB</span>
                <span class="badge rounded-pill bg-primary">{{ $group['distinct_parent_rows'] }} parent IDs (students)</span>
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
              <input type="hidden" name="link_context" value="integrity_report">
              @foreach(['dup_limit','page'] as $qk)
                @if(request()->filled($qk))
                  <input type="hidden" name="{{ $qk }}" value="{{ request($qk) }}">
                @endif
              @endforeach
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
              <div class="duplicate-actions">
                <button type="submit" class="btn btn-settings-primary btn-sm link-submit">
                  <i class="bi bi-link-45deg"></i> Link selected as siblings
                </button>
              </div>
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
                <span class="badge rounded-pill bg-secondary">{{ $group['count_parents_db'] }} rows in DB</span>
                <span class="badge rounded-pill bg-primary">{{ $group['distinct_parent_rows'] }} parent IDs (students)</span>
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
              <input type="hidden" name="link_context" value="integrity_report">
              @foreach(['dup_limit','page'] as $qk)
                @if(request()->filled($qk))
                  <input type="hidden" name="{{ $qk }}" value="{{ request($qk) }}">
                @endif
              @endforeach
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
              <div class="duplicate-actions">
                <button type="submit" class="btn btn-settings-primary btn-sm link-submit">
                  <i class="bi bi-link-45deg"></i> Link selected as siblings
                </button>
              </div>
            </form>
          </div>
        @empty
          <p class="text-muted mb-0">No duplicate email groups (within limits).</p>
        @endforelse
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
            <button type="submit" class="btn btn-settings-primary w-100 w-md-auto"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
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
</script>
@endpush
