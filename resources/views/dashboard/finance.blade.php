{{-- resources/views/dashboard/finance.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
      <div>
        <span class="crumb">Dashboard</span>
        <h2 class="mb-1">Finance Dashboard</h2>
        <p class="mb-0">Overview of financial activities and metrics.</p>
      </div>
      <div class="actions">
        <a class="dash-chip" href="{{ route('finance.invoices.index') }}"><i class="bi bi-receipt"></i> Invoices</a>
        <a class="dash-chip" href="{{ route('finance.fee-structures.manage') }}"><i class="bi bi-cash-coin"></i> Fee Structure</a>
      </div>
    </div>

    @include('dashboard.partials.filters')

    @include('dashboard.partials.kpis')         {{-- will show finance cards because $role=finance --}}
    @include('dashboard.partials.finance_donut', ['kpis' => $kpis, 'finance' => $charts['finance']])

    <div class="row g-3 mt-1">
      <div class="col-lg-8">
        @include('dashboard.partials.invoice_table', ['invoices' => $invoices])
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.announcements', ['announcements' => $announcements])
      </div>
    </div>
  </div>
</div>
@endsection
