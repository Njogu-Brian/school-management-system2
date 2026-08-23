@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
      <div>
        <span class="crumb">Finance</span>
        <h2 class="mb-1">{{ $greeting ?? 'Welcome' }}, {{ auth()->user()->name }}</h2>
        <p class="mb-0">Start with outstanding balances, then record payments and invoices.</p>
      </div>
    </div>

    @include('dashboard.partials.filters')

    <div class="erp-quick-grid mb-3">
      @if(Route::has('finance.payments.create'))
        <a class="erp-quick-btn" href="{{ route('finance.payments.create') }}"><i class="bi bi-cash"></i> Record Payment</a>
      @endif
      @if(Route::has('finance.invoices.index'))
        <a class="erp-quick-btn" href="{{ route('finance.invoices.index') }}"><i class="bi bi-receipt"></i> Create Invoice</a>
      @endif
      @if(Route::has('finance.student-statements.index'))
        <a class="erp-quick-btn" href="{{ route('finance.student-statements.index') }}"><i class="bi bi-search"></i> Search Student</a>
      @endif
      @if(Route::has('finance.fee-balances.index'))
        <a class="erp-quick-btn" href="{{ route('finance.fee-balances.index') }}"><i class="bi bi-wallet2"></i> View Outstanding</a>
      @endif
      @if(Route::has('finance.payments.index'))
        <a class="erp-quick-btn" href="{{ route('finance.payments.index') }}"><i class="bi bi-printer"></i> Print Receipt</a>
      @endif
      @if(Route::has('finance.student-statements.index'))
        <a class="erp-quick-btn" href="{{ route('finance.student-statements.index') }}"><i class="bi bi-file-earmark-text"></i> Fee Statement</a>
      @endif
      @if(Route::has('finance.accountant-dashboard.index'))
        <a class="erp-quick-btn" href="{{ route('finance.accountant-dashboard.index') }}"><i class="bi bi-bar-chart"></i> Finance Reports</a>
      @elseif(Route::has('finance.fee-balances.index'))
        <a class="erp-quick-btn" href="{{ route('finance.fee-balances.index') }}"><i class="bi bi-bar-chart"></i> Finance Reports</a>
      @endif
    </div>

    @include('dashboard.partials.kpis')

    <div class="row g-3">
      <div class="col-lg-8">
        @include('dashboard.partials.outstanding_students')
        <div class="mt-3">@include('dashboard.partials.invoice_table')</div>
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.finance_donut')
        <div class="dash-card card mb-3 mt-3">
          <div class="card-header"><strong>Collection by method</strong></div>
          <div class="card-body">
            @forelse($paymentMethods ?? [] as $row)
              <div class="d-flex justify-content-between mb-2">
                <span>{{ $row->method }}</span>
                <strong>{{ format_money($row->total) }}</strong>
              </div>
            @empty
              <div class="erp-empty"><i class="bi bi-credit-card"></i>No payment records for this period.</div>
            @endforelse
          </div>
        </div>
        <div class="dash-card card mb-3">
          <div class="card-header d-flex justify-content-between">
            <strong>Recent payments</strong>
            @if(Route::has('finance.payments.index'))
              <a class="small dash-btn-ghost" href="{{ route('finance.payments.index') }}">View all</a>
            @endif
          </div>
          <div class="card-body">
            @forelse($recentPayments ?? [] as $p)
              <div class="dash-list-item d-flex justify-content-between gap-2">
                <div>
                  <div class="fw-semibold">{{ $p->student_name ?: $p->receipt_number }}</div>
                  <div class="small dash-muted">{{ $p->method }} · {{ $p->receipt_number }}</div>
                </div>
                <div class="text-end">
                  <div class="fw-semibold">{{ format_money($p->amount) }}</div>
                </div>
              </div>
            @empty
              <div class="erp-empty"><i class="bi bi-cash-stack"></i>No payment records available.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @include('dashboard.partials.charts_js_bootstrap')
@endpush
