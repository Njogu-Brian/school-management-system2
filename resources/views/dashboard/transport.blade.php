{{-- resources/views/dashboard/transport.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero mb-3">
      <span class="crumb">Dashboard</span>
      <h2 class="mb-1">Transport Dashboard</h2>
      <p class="mb-0">Snapshot of trips, vehicles and announcements.</p>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        @include('dashboard.partials.transport_widget', ['transport' => $transport])
        {{-- add a trip frequency chart later if you like --}}
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.announcements', ['announcements' => $announcements])
      </div>
    </div>
  </div>
</div>
@endsection
