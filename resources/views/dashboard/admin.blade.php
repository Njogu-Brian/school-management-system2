@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
      <div>
        <span class="crumb">{{ $schoolName ?? 'Dashboard' }}</span>
        <h2 class="mb-1">{{ $greeting ?? 'Welcome' }}, {{ auth()->user()->name }}</h2>
        <p class="mb-0">School operations command centre.</p>
        <div class="d-flex flex-wrap gap-2 mt-2">
          @if(!empty($selectedYear))<span class="dash-chip">{{ $selectedYear->year ?? $selectedYear->name }}</span>@endif
          @if(!empty($selectedTerm))<span class="dash-chip">{{ $selectedTerm->name }}</span>@endif
        </div>
      </div>
    </div>

    @includeWhen(session('success') || session('error'),'dashboard.partials.flash')
    @include('dashboard.partials.filters')

    <div class="dash-section-label">Urgent</div>
    @include('dashboard.partials.alerts')

    <div class="dash-section-label">Today at a glance</div>
    <div class="dash-card card card-body mb-3">
      @include('dashboard.partials.quick_actions')
    </div>
    @include('dashboard.partials.kpis')

    <div class="dash-section-label">Financial position</div>
    <div class="row g-3 mb-3">
      <div class="col-lg-7">@include('dashboard.partials.finance_donut')</div>
      <div class="col-lg-5">@include('dashboard.partials.invoice_table')</div>
    </div>

    <div class="dash-section-label">Attendance</div>
    <div class="row g-3 mb-3">
      <div class="col-lg-7">@include('dashboard.partials.attendance_chart')</div>
      <div class="col-lg-5">@include('dashboard.partials.absence_table')</div>
    </div>

    <div class="dash-section-label">Academic activity</div>
    <div class="row g-3 mb-3">
      <div class="col-lg-7">@include('dashboard.partials.exam_performance')</div>
      <div class="col-lg-5">@include('dashboard.partials.upcoming')</div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-lg-4">
        <div class="dash-section-label">Staff</div>
        <div class="dash-card card">
          <div class="card-body">
            <div class="fs-4 fw-semibold">{{ number_format($kpis['staff_active'] ?? 0) }}</div>
            <div class="dash-muted small">Active staff · {{ number_format($kpis['teachers_on_leave'] ?? 0) }} on leave today</div>
            @if(Route::has('staff.leave-requests.index'))
              <a href="{{ route('staff.leave-requests.index') }}" class="btn btn-outline-primary btn-sm mt-2">Leave requests</a>
            @endif
          </div>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="dash-section-label">Transport</div>
        @include('dashboard.partials.today_trips')
      </div>
    </div>

    <div class="dash-section-label">Overview</div>
    <div class="row g-3 mb-3">
      <div class="col-xl-8">@include('dashboard.partials.overview')</div>
      <div class="col-xl-4">@include('dashboard.partials.recent_admissions')</div>
    </div>

    <div class="dash-section-label">Recent activity</div>
    <div class="row g-3">
      <div class="col-lg-7">@include('dashboard.partials.activity')</div>
      <div class="col-lg-5">
        @include('dashboard.partials.announcements')
        @include('dashboard.partials.behaviour_widget')
      </div>
    </div>
  </div>
</div>

@if(in_array($role ?? 'admin', ['admin','finance']))
<div class="modal fade" id="voteheadBreakdownModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Votehead Breakdown - Total Invoiced</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        @php $m = fn($v) => format_money($v); $totalInvoiced = collect($voteheadBreakdown ?? [])->sum('total_amount'); @endphp
        <div class="mb-3"><strong>Total Invoiced: {{ $m($totalInvoiced) }}</strong></div>
        @if(!empty($voteheadBreakdown) && count($voteheadBreakdown) > 0)
          <div class="table-responsive">
            <table class="table table-sm table-hover">
              <thead><tr><th>Votehead</th><th>Code</th><th class="text-end">Amount</th><th class="text-end">Percentage</th></tr></thead>
              <tbody>
                @foreach($voteheadBreakdown as $item)
                  @php $percentage = $totalInvoiced > 0 ? ($item['total_amount'] / $totalInvoiced) * 100 : 0; @endphp
                  <tr>
                    <td>{{ $item['votehead_name'] }}</td>
                    <td><code>{{ $item['votehead_code'] }}</code></td>
                    <td class="text-end">{{ $m($item['total_amount']) }}</td>
                    <td class="text-end">{{ number_format($percentage, 2) }}%</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No invoice data available for the selected period.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @include('dashboard.partials.charts_js_bootstrap')
@endpush
