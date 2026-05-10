@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      .integrity-missing .integrity-hero-icon {
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
      .integrity-missing .gap-pill {
        font-size: 0.7rem;
        font-weight: 600;
      }
      .integrity-missing .contact-preview {
        font-size: 0.82rem;
        line-height: 1.35;
      }
      .integrity-missing .stack-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        justify-content: flex-end;
      }
      @media (max-width: 767.98px) {
        .integrity-missing .settings-shell {
          padding-left: 0.5rem;
          padding-right: 0.5rem;
        }
        .integrity-missing .stack-actions {
          justify-content: flex-start;
        }
        .integrity-missing .integrity-actions-row .btn {
          flex: 1 1 auto;
          min-width: 9rem;
        }
      }
    </style>
@endpush

@section('content')
@php
  $ccOpts = $countryCodes ?? [['code' => '+254', 'label' => 'Kenya (+254)']];

  $quickPayload = function ($stu) use ($perBoth, $perOne) {
      $p = $stu->parent;
      $fatherCc = $p->father_phone_country_code ?? '+254';
      $motherCc = $p->mother_phone_country_code ?? '+254';
      return [
          'student_id' => $stu->id,
          'label' => $stu->admission_number.' — '.$stu->full_name,
          'father_cc' => $fatherCc,
          'mother_cc' => $motherCc,
          'father_wa_cc' => $p->father_whatsapp_country_code ?? $fatherCc,
          'mother_wa_cc' => $p->mother_whatsapp_country_code ?? $motherCc,
          'father_name_empty' => ! filled($p->father_name ?? null),
          'father_phone_empty' => ! filled($p->father_phone ?? null),
          'father_whatsapp_empty' => ! filled($p->father_whatsapp ?? null),
          'father_email_empty' => ! filled($p->father_email ?? null),
          'mother_name_empty' => ! filled($p->mother_name ?? null),
          'mother_phone_empty' => ! filled($p->mother_phone ?? null),
          'mother_whatsapp_empty' => ! filled($p->mother_whatsapp ?? null),
          'mother_email_empty' => ! filled($p->mother_email ?? null),
          'return_route' => 'families.integrity-report.missing-contacts',
          'ret_both_page' => request('both_page'),
          'ret_one_page' => request('one_page'),
          'ret_per_both' => $perBoth,
          'ret_per_one' => $perOne,
      ];
  };

  $previewLine = function (?string $name, ?string $phone): string {
      $n = filled($name) ? $name : '—';
      $ph = filled($phone) ? $phone : '—';
      return $n.' · '.$ph;
  };
@endphp

<div class="settings-page integrity-missing">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div class="d-flex gap-3 align-items-start">
        <span class="integrity-hero-icon"><i class="bi bi-person-lines-fill"></i></span>
        <div>
          <div class="crumb">
            <a href="{{ route('families.index') }}" class="text-reset text-decoration-none">Families</a>
            <span class="text-muted"> / </span>
            <a href="{{ route('families.integrity-report') }}" class="text-reset text-decoration-none">Integrity report</a>
          </div>
          <h1 class="mb-1">Missing contacts</h1>
          <p class="text-muted mb-2 mb-md-0">Active students only. Patch blank father/mother name, phone, WhatsApp, and email without opening the full admission form.</p>
          <div class="d-flex flex-wrap gap-2">
            <span class="pill-badge pill-warning">{{ $missingBoth->total() }} missing both phones</span>
            <span class="pill-badge pill-secondary">{{ $missingOne->total() }} missing one phone</span>
          </div>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap integrity-actions-row">
        <a href="{{ route('families.integrity-report') }}" class="btn btn-ghost-strong"><i class="bi bi-shield-exclamation"></i> Duplicate report</a>
        <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Families</a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-4">
      <div class="card-header">
        <h5 class="mb-1">Missing both father and mother phone</h5>
        <p class="text-muted small mb-0">Students whose parent record has no father phone and no mother phone.</p>
      </div>
      <div class="card-body">
        @forelse($missingBoth as $stu)
          @if($loop->first)
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
          @endif
                  @php $p = $stu->parent; $qp = $quickPayload($stu); @endphp
                  <tr>
                    <td class="fw-semibold text-nowrap">{{ $stu->admission_number }}</td>
                    <td>{{ $stu->full_name }}</td>
                    <td class="text-nowrap">{{ $stu->classroom->name ?? '—' }}</td>
                    <td>
                      <span class="badge rounded-pill text-bg-danger gap-pill">Father phone</span>
                      <span class="badge rounded-pill text-bg-danger gap-pill">Mother phone</span>
                      @if(! filled($p->father_name ?? null))
                        <span class="badge rounded-pill bg-secondary gap-pill">Father name</span>
                      @endif
                      @if(! filled($p->mother_name ?? null))
                        <span class="badge rounded-pill bg-secondary gap-pill">Mother name</span>
                      @endif
                    </td>
                    <td class="contact-preview">{{ $previewLine($p->father_name ?? null, $p->father_phone ?? null) }}</td>
                    <td class="contact-preview">{{ $previewLine($p->mother_name ?? null, $p->mother_phone ?? null) }}</td>
                    <td class="text-end">
                      <div class="stack-actions">
                        @if(Route::has('families.integrity-report.quick-parent-phones'))
                          <button type="button"
                                  class="btn btn-sm btn-settings-primary quick-contact-open"
                                  data-payload="{{ e(json_encode($qp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) }}">
                            <i class="bi bi-lightning-charge"></i> Quick edit
                          </button>
                        @endif
                        @if(Route::has('students.edit'))
                          <a href="{{ route('students.edit', $stu->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Full edit</a>
                        @endif
                      </div>
                    </td>
                  </tr>
          @if($loop->last)
                </tbody>
              </table>
            </div>
          @endif
        @empty
          <p class="text-muted mb-0">No students in this category.</p>
        @endforelse
        @if($missingBoth->isNotEmpty())
          {{ $missingBoth->links() }}
        @endif
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h5 class="mb-1">Missing exactly one phone</h5>
        <p class="text-muted small mb-0">Father phone filled but mother blank, or the reverse.</p>
      </div>
      <div class="card-body">
        @forelse($missingOne as $stu)
          @if($loop->first)
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
          @endif
                  @php
                    $p = $stu->parent;
                    $qp = $quickPayload($stu);
                    $mf = ! filled($p->father_phone ?? null);
                    $mm = ! filled($p->mother_phone ?? null);
                  @endphp
                  <tr>
                    <td class="fw-semibold text-nowrap">{{ $stu->admission_number }}</td>
                    <td>{{ $stu->full_name }}</td>
                    <td class="text-nowrap">{{ $stu->classroom->name ?? '—' }}</td>
                    <td>
                      @if($mf)
                        <span class="badge rounded-pill text-bg-danger gap-pill">Father phone</span>
                      @endif
                      @if($mm)
                        <span class="badge rounded-pill text-bg-danger gap-pill">Mother phone</span>
                      @endif
                    </td>
                    <td class="contact-preview">{{ $previewLine($p->father_name ?? null, $p->father_phone ?? null) }}</td>
                    <td class="contact-preview">{{ $previewLine($p->mother_name ?? null, $p->mother_phone ?? null) }}</td>
                    <td class="text-end">
                      <div class="stack-actions">
                        @if(Route::has('families.integrity-report.quick-parent-phones'))
                          <button type="button"
                                  class="btn btn-sm btn-settings-primary quick-contact-open"
                                  data-payload="{{ e(json_encode($qp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)) }}">
                            <i class="bi bi-lightning-charge"></i> Quick edit
                          </button>
                        @endif
                        @if(Route::has('students.edit'))
                          <a href="{{ route('students.edit', $stu->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Full edit</a>
                        @endif
                      </div>
                    </td>
                  </tr>
          @if($loop->last)
                </tbody>
              </table>
            </div>
          @endif
        @empty
          <p class="text-muted mb-0">No students in this category.</p>
        @endforelse
        @if($missingOne->isNotEmpty())
          {{ $missingOne->links() }}
        @endif
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-sliders me-1"></i> Pagination</h6>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('families.integrity-report.missing-contacts') }}" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label small text-muted">“Both missing” per page</label>
            <input type="number" name="per_both" class="form-control" min="5" max="100" value="{{ $perBoth }}">
          </div>
          <div class="col-md-4">
            <label class="form-label small text-muted">“One missing” per page</label>
            <input type="number" name="per_one" class="form-control" min="5" max="100" value="{{ $perOne }}">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-settings-primary w-100 w-md-auto"><i class="bi bi-arrow-clockwise"></i> Apply</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@include('families.partials.quick_contact_modal', ['ccOpts' => $ccOpts])
@endsection
