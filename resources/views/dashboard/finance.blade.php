{{-- resources/views/dashboard/finance.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
  @include('finance.partials.header', [
      'title' => 'Finance Dashboard',
      'icon' => 'bi bi-graph-up',
      'subtitle' => 'Overview of financial activities and metrics'
  ])

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
@endsection
