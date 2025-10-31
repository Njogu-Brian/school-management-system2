{{-- resources/views/dashboard/finance.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-xxl">
  <h2 class="mb-3">Finance Dashboard</h2>

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
