@extends('layouts.app')

@section('content')
<div class="finance-page transport-fees-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Transport Fees',
        'icon' => 'bi bi-bus-front',
        'subtitle' => 'Manage transport charges per term and keep invoices in sync',
        'actions' => '<a href="' . route('finance.transport-fees.import') . '" class="btn btn-finance btn-finance-primary btn-finance-lg"><i class="bi bi-upload me-2"></i>Import</a>'
    ])

    @if(session('success'))
      <div class="transport-alert transport-alert-success alert alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="transport-alert transport-alert-error alert alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Filters (collapsible on mobile) --}}
    <div class="transport-filter-card finance-filter-card finance-animate">
      <div class="transport-filter-header" data-bs-toggle="collapse" data-bs-target="#transportFilters" aria-expanded="true">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-funnel"></i>
          <span>Filters</span>
        </div>
        <i class="bi bi-chevron-down transport-filter-chevron"></i>
      </div>
      <div class="collapse show" id="transportFilters">
        <form method="GET" class="transport-filter-form">
          <div class="row g-3 g-md-4">
            <div class="col-12 col-sm-6 col-lg-4">
              <label class="finance-form-label">Classroom</label>
              <select name="classroom_id" class="finance-form-select transport-filter-select" onchange="this.form.submit()">
                <option value="">Select class to load data</option>
                @foreach($classrooms as $class)
                  <option value="{{ $class->id }}" @selected($classroomId == $class->id)>{{ $class->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
              <label class="finance-form-label">Year</label>
              <select name="year" class="finance-form-select" onchange="this.form.submit()">
                @foreach(\App\Models\AcademicYear::orderByDesc('year')->get() as $ay)
                  <option value="{{ $ay->year }}" @selected($year == $ay->year)>{{ $ay->year }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6 col-sm-3 col-lg-2">
              <label class="finance-form-label">Term</label>
              <select name="term" class="finance-form-select" onchange="this.form.submit()">
                @php
                  $termsForYear = ($allTerms ?? collect())->filter(fn($t) => $t->academicYear && $t->academicYear->year == $year);
                  $termNum = fn($t) => (int) preg_replace('/[^0-9]/', '', $t->name) ?: 1;
                @endphp
                @foreach($termsForYear as $t)
                  <option value="{{ $termNum($t) }}" @selected($term == $termNum($t))>{{ $t->name }}</option>
                @endforeach
                @if($termsForYear->isEmpty())
                  <option value="{{ $term }}">Term {{ $term }}</option>
                @endif
              </select>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row g-4 transport-main-row">
      {{-- Main content: table --}}
      <div class="col-12 col-xl-8">
        <div class="transport-main-card finance-card finance-animate">
          <div class="finance-card-header transport-card-header">
            <i class="bi bi-clipboard-data"></i>
            <span>Current transport charges</span>
            @if($students->count())
            <span class="transport-badge ms-auto">{{ $students->count() }} student(s)</span>
            @endif
          </div>
          <div class="finance-card-body transport-card-body p-0">
            @if($students->count())
            <form method="POST" action="{{ route('finance.transport-fees.bulk-update') }}">
              @csrf
              <input type="hidden" name="year" value="{{ $year }}">
              <input type="hidden" name="term" value="{{ $term }}">

              {{-- Desktop table --}}
              <div class="transport-table-wrapper d-none d-lg-block">
                <div class="table-responsive">
                  <table class="finance-table transport-table align-middle">
                    <thead>
                      <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Morning Drop-off</th>
                        <th>Evening Drop-off</th>
                        <th>Drop-off point <small class="text-muted">(Legacy)</small></th>
                        <th class="text-end">Amount (KES)</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($students as $student)
                        @php
                          $fee = $feeMap[$student->id] ?? null;
                          $assignment = $assignmentMap[$student->id] ?? null;
                          $amount = old("fees.{$student->id}.amount", $fee?->amount);
                          $amount = $amount !== null ? $amount : '';
                        @endphp
                        <tr class="transport-table-row">
                          <td>
                            <div class="transport-student-name">{{ $student->full_name }}</div>
                            <div class="transport-student-adm">Adm: {{ $student->admission_number }}</div>
                          </td>
                          <td>{{ $student->classroom?->name ?? '—' }}</td>
                          <td>
                            <select name="fees[{{ $student->id }}][morning_drop_off_point_id]" class="finance-form-select">
                              <option value="">—</option>
                              @foreach($dropOffPoints as $point)
                                <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.morning_drop_off_point_id", $assignment?->morning_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                              @endforeach
                            </select>
                          </td>
                          <td>
                            <select name="fees[{{ $student->id }}][evening_drop_off_point_id]" class="finance-form-select">
                              <option value="">—</option>
                              @foreach($dropOffPoints as $point)
                                <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.evening_drop_off_point_id", $assignment?->evening_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                              @endforeach
                            </select>
                          </td>
                          <td>
                            <select name="fees[{{ $student->id }}][drop_off_point_id]" class="finance-form-select">
                              <option value="">—</option>
                              @foreach($dropOffPoints as $point)
                                <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.drop_off_point_id", $fee?->drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                              @endforeach
                            </select>
                            <input type="text" name="fees[{{ $student->id }}][drop_off_point_name]" class="finance-form-control mt-2" placeholder="Other / custom" value="{{ old("fees.{$student->id}.drop_off_point_name", $fee?->drop_off_point_name) }}">
                          </td>
                          <td class="text-end">
                            <input type="number" step="0.01" name="fees[{{ $student->id }}][amount]" class="finance-form-control transport-amount-input" placeholder="0.00" value="{{ $amount }}">
                            <small class="transport-amount-hint">Leave empty for drop-off only</small>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                    <tfoot>
                      <tr>
                        <th colspan="5" class="text-end">Current total</th>
                        <th class="text-end transport-total">{{ number_format($totalAmount, 2) }}</th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              {{-- Mobile card layout --}}
              <div class="transport-mobile-cards d-lg-none">
                @foreach($students as $student)
                  @php
                    $fee = $feeMap[$student->id] ?? null;
                    $assignment = $assignmentMap[$student->id] ?? null;
                    $amount = old("fees.{$student->id}.amount", $fee?->amount);
                    $amount = $amount !== null ? $amount : '';
                  @endphp
                  <div class="transport-mobile-card">
                    <div class="transport-mobile-card-header">
                      <div>
                        <div class="transport-student-name">{{ $student->full_name }}</div>
                        <div class="transport-student-adm">{{ $student->admission_number }} · {{ $student->classroom?->name ?? '—' }}</div>
                      </div>
                    </div>
                    <div class="transport-mobile-card-body">
                      <div class="transport-mobile-field">
                        <label>Morning</label>
                        <select name="fees[{{ $student->id }}][morning_drop_off_point_id]" class="finance-form-select">
                          <option value="">—</option>
                          @foreach($dropOffPoints as $point)
                            <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.morning_drop_off_point_id", $assignment?->morning_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="transport-mobile-field">
                        <label>Evening</label>
                        <select name="fees[{{ $student->id }}][evening_drop_off_point_id]" class="finance-form-select">
                          <option value="">—</option>
                          @foreach($dropOffPoints as $point)
                            <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.evening_drop_off_point_id", $assignment?->evening_drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="transport-mobile-field">
                        <label>Drop-off</label>
                        <select name="fees[{{ $student->id }}][drop_off_point_id]" class="finance-form-select">
                          <option value="">—</option>
                          @foreach($dropOffPoints as $point)
                            <option value="{{ $point->id }}" @selected(old("fees.{$student->id}.drop_off_point_id", $fee?->drop_off_point_id) == $point->id)>{{ $point->name }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="fees[{{ $student->id }}][drop_off_point_name]" class="finance-form-control mt-1" placeholder="Other" value="{{ old("fees.{$student->id}.drop_off_point_name", $fee?->drop_off_point_name) }}">
                      </div>
                      <div class="transport-mobile-field transport-mobile-amount">
                        <label>Amount (KES)</label>
                        <input type="number" step="0.01" name="fees[{{ $student->id }}][amount]" class="finance-form-control" placeholder="0.00" value="{{ $amount }}">
                      </div>
                    </div>
                  </div>
                @endforeach
                <div class="transport-mobile-total">
                  <span>Total</span>
                  <strong>{{ number_format($totalAmount, 2) }} KES</strong>
                </div>
              </div>

              <div class="transport-form-footer">
                <button type="submit" class="btn btn-finance btn-finance-primary btn-finance-lg">
                  <i class="bi bi-save me-2"></i>Update transport fees
                </button>
                <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-finance btn-finance-outline">
                  <i class="bi bi-geo-alt me-2"></i>Manage drop-off points
                </a>
              </div>
            </form>
            @else
            <div class="transport-empty-state">
              <div class="transport-empty-icon">
                <i class="bi bi-bus-front"></i>
              </div>
              <h3 class="transport-empty-title">
                @if(!$classroomId)
                  Select a classroom to load data
                @else
                  No students found
                @endif
              </h3>
              <p class="transport-empty-desc">
                @if(!$classroomId)
                  Choose a classroom from the filters above to view and manage transport fees.
                @else
                  No students with transport assignments found for this filter.
                @endif
              </p>
            </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Sidebar: Duplicate only (Import & History in submenu) --}}
      <div class="col-12 col-xl-4">
        <div class="transport-sidebar">
          @include('finance.transport_fees.partials.duplicate_form', ['classrooms' => $classrooms, 'year' => $year, 'term' => $term, 'termsByYear' => $termsByYear ?? collect(), 'futureTerms' => $futureTerms ?? collect()])
        </div>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  /* Transport Fees – styles.md compliant, responsive */
  .transport-fees-page { --transport-gap: 16px; --transport-gap-lg: 24px; }
  @media (min-width: 768px) { .transport-fees-page { --transport-gap: 20px; --transport-gap-lg: 32px; } }
  @media (min-width: 1024px) { .transport-fees-page { --transport-gap: 24px; --transport-gap-lg: 32px; } }

  .transport-alert { padding: 14px 18px; border-radius: 12px; display: flex; align-items: center; margin-bottom: var(--transport-gap); }
  .transport-alert-success { background: #ecfdf3; color: #166534; border: 1px solid #bbf7d0; }
  .transport-alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

  .transport-filter-card { padding: 0; overflow: hidden; border-radius: 14px; }
  .transport-filter-header { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; background: color-mix(in srgb, var(--fin-primary) 6%, #fff 94%); border-bottom: 1px solid var(--fin-border); font-weight: 600; color: var(--fin-text); }
  .transport-filter-chevron { transition: transform 0.3s; color: var(--fin-muted); }
  [aria-expanded="true"] .transport-filter-chevron { transform: rotate(180deg); }
  .transport-filter-form { padding: 18px; }

  .transport-main-card { padding: 0; overflow: hidden; }
  .transport-card-header { font-weight: 700; color: var(--fin-primary); }
  .transport-badge { font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 999px; background: color-mix(in srgb, var(--fin-primary) 12%, #fff 88%); color: var(--fin-primary); }
  .transport-card-body { padding: 0 !important; }

  .transport-table-wrapper { border-radius: 0 0 14px 14px; overflow: hidden; }
  .transport-table thead th { font-size: 13px; padding: 12px 16px; }
  .transport-table td, .transport-table th { padding: 12px 16px; }
  .transport-table td select { min-width: 140px; }
  .transport-student-name { font-weight: 600; color: var(--fin-text); }
  .transport-student-adm { font-size: 12px; color: var(--fin-muted); }
  .transport-amount-input { max-width: 120px; }
  .transport-amount-hint { font-size: 11px; color: var(--fin-muted); }
  .transport-total { font-size: 1.1rem; color: var(--fin-primary); }
  .transport-table tfoot { background: color-mix(in srgb, var(--fin-primary) 6%, #fff 94%); border-top: 2px solid var(--fin-border); }

  .transport-form-footer { padding: 18px; border-top: 1px solid var(--fin-border); display: flex; flex-wrap: wrap; gap: 12px; }
  @media (max-width: 575px) { .transport-form-footer { flex-direction: column; } .transport-form-footer .btn { width: 100%; } }

  .transport-empty-state { padding: 48px 24px; text-align: center; }
  .transport-empty-icon { width: 80px; height: 80px; margin: 0 auto 20px; border-radius: 50%; background: color-mix(in srgb, var(--fin-primary) 10%, #fff 90%); display: flex; align-items: center; justify-content: center; color: var(--fin-primary); font-size: 2rem; }
  .transport-empty-title { font-size: 1.25rem; font-weight: 700; color: var(--fin-text); margin-bottom: 8px; }
  .transport-empty-desc { color: var(--fin-muted); font-size: 0.9rem; max-width: 320px; margin: 0 auto; }

  .transport-mobile-cards { padding: 16px; display: flex; flex-direction: column; gap: 16px; }
  .transport-mobile-card { background: var(--fin-bg); border: 1px solid var(--fin-border); border-radius: 12px; overflow: hidden; }
  .transport-mobile-card-header { padding: 14px 16px; border-bottom: 1px solid var(--fin-border); }
  .transport-mobile-card-body { padding: 16px; display: grid; gap: 12px; }
  .transport-mobile-field label { display: block; font-size: 12px; font-weight: 600; color: var(--fin-muted); margin-bottom: 6px; }
  .transport-mobile-amount input { font-weight: 600; }
  .transport-mobile-total { padding: 16px; display: flex; justify-content: space-between; align-items: center; background: color-mix(in srgb, var(--fin-primary) 8%, #fff 92%); border-top: 1px solid var(--fin-border); font-weight: 600; }

  .transport-sidebar { display: flex; flex-direction: column; gap: var(--transport-gap); }
  .btn-finance-lg { padding: 12px 20px; font-size: 14px; min-height: 44px; }

  .transport-tabs { border-bottom: 1px solid var(--fin-border); }
  .transport-tabs .nav-link { border: none; border-radius: 0; color: var(--fin-muted); font-weight: 600; padding: 10px 16px; }
  .transport-tabs .nav-link:hover { color: var(--fin-primary); }
  .transport-tabs .nav-link.active { color: var(--fin-primary); border-bottom: 2px solid var(--fin-primary); background: transparent; }
  .transport-info-pill { padding: 10px 14px; border-radius: 10px; background: color-mix(in srgb, var(--fin-primary) 8%, #fff 92%); color: var(--fin-text); font-size: 12px; }

  body.theme-dark .transport-alert-success { background: #052e16; color: #86efac; border-color: #166534; }
  body.theme-dark .transport-alert-error { background: #450a0a; color: #fca5a5; border-color: #b91c1c; }
  body.theme-dark .transport-mobile-card { background: var(--fin-bg); }
  body.theme-dark .transport-info-pill { background: color-mix(in srgb, var(--fin-primary) 15%, var(--fin-surface) 85%); }
</style>
@endpush
@endsection

