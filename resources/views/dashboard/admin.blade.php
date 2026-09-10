@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page ds-pilot">
  <div class="dashboard-shell">
    <x-page-header eyebrow="{{ $schoolName ?? 'Dashboard' }}" title="{{ ($greeting ?? 'Welcome') . ', ' . auth()->user()->name }}" description="School operations command centre." class="dash-hero mb-3">
      <x-slot:actions>
        <div class="d-flex flex-wrap gap-2">
          @if(!empty($selectedYear))<span class="dash-chip">{{ $selectedYear->year ?? $selectedYear->name }}</span>@endif
          @if(!empty($selectedTerm))<span class="dash-chip">{{ $selectedTerm->name }}</span>@endif
        </div>
      </x-slot:actions>
    </x-page-header>

    <x-feedback.flash />
    @include('dashboard.partials.filters')

    <div class="dash-section-label">Today at a glance</div>
    <div class="row g-3 mb-3">
      <div class="col-6 col-xl-3">
        <x-data.stat-card label="Active students" :value="format_number($kpis['students'] ?? 0)" trend="{{ ($kpis['attendance_pct'] ?? null) !== null ? format_number($kpis['attendance_pct'], 1) . '% present today' : 'Current scope' }}" icon="bi bi-people" />
      </div>
      <div class="col-6 col-xl-3">
        <x-data.stat-card label="Attendance today" :value="($kpis['attendance_pct'] ?? null) !== null ? format_number($kpis['attendance_pct'], 1) . '%' : '—'" trend="{{ format_number($kpis['absent_today'] ?? 0) }} absent · {{ format_number($kpis['unmarked_today'] ?? 0) }} unmarked" icon="bi bi-clipboard-check" />
      </div>
      @if(in_array($role ?? 'admin', ['admin', 'finance']))
        <div class="col-6 col-xl-3">
          <x-data.stat-card label="Collected" :value="format_money($kpis['fees_collected'] ?? 0)" trend="{{ ($kpis['collection_rate'] ?? null) !== null ? format_number($kpis['collection_rate'], 1) . '% of invoiced' : 'Selected period' }}" icon="bi bi-cash-coin" />
        </div>
        <div class="col-6 col-xl-3">
          <x-data.stat-card label="Outstanding" :value="format_money($kpis['fees_outstanding'] ?? 0)" trend="{{ format_number($kpis['owing_students'] ?? 0) }} students owing" icon="bi bi-wallet2" />
        </div>
      @else
        <div class="col-6 col-xl-3">
          <x-data.stat-card label="Active staff" :value="format_number($kpis['staff_active'] ?? 0)" trend="{{ format_number($kpis['teachers_on_leave'] ?? 0) }} on leave today" icon="bi bi-person-badge" />
        </div>
        <div class="col-6 col-xl-3">
          <x-data.stat-card label="Pending approvals" :value="format_number($kpis['pending_approvals'] ?? 0)" trend="Needs review" icon="bi bi-check2-square" />
        </div>
      @endif
    </div>

    <div class="dash-section-label">Priority work</div>
    <div class="row g-3 mb-3">
      <div class="col-lg-7">@include('dashboard.partials.alerts')</div>
      <div class="col-lg-5">
        <div class="dash-card card h-100">
          <div class="card-header"><strong>Quick actions</strong></div>
          <div class="card-body">@include('dashboard.partials.quick_actions')</div>
        </div>
      </div>
    </div>

    <div class="dash-section-label">Operational summaries</div>
    <div class="row g-3">
      @if(in_array($role ?? 'admin', ['admin', 'finance']))
        <div class="col-lg-6">@include('dashboard.partials.finance_donut')</div>
      @endif
      <div class="col-lg-6">@include('dashboard.partials.attendance_chart')</div>
    </div>
    <div class="dashboard-secondary-links mt-3">
      <span class="dash-muted small">More detail:</span>
      @if(Route::has('finance.fee-balances.index') && in_array($role ?? 'admin', ['admin', 'finance']))<a href="{{ route('finance.fee-balances.index') }}">Fee balances</a>@endif
      @if(Route::has('attendance.records'))<a href="{{ route('attendance.records') }}">Attendance reports</a>@endif
      @if(Route::has('reports.class-reports.index'))<a href="{{ route('reports.class-reports.index') }}">Class reports</a>@endif
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
