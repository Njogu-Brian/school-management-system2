{{-- resources/views/dashboard/parent.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero mb-3">
      <span class="crumb">Dashboard</span>
      <h2 class="mb-1">Parent Dashboard</h2>
      <p class="mb-0">Attendance, behaviour, announcements and invoices for your child(ren).</p>
    </div>

    {{-- parents don’t need global filters; hide finance & transport automatically via $role --}}

    <div class="row g-3">
      <div class="col-lg-8">
        @include('dashboard.partials.attendance_line', ['attendance' => $charts['attendance']])
        {{-- optional: a slim behaviour widget for their child(ren) if you add scoping --}}
        @include('dashboard.partials.behaviour_widget', ['behaviour' => $behaviour])
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.announcements', ['announcements' => $announcements])
        @include('dashboard.partials.upcoming', ['upcoming' => $upcoming])
        {{-- invoices table makes sense for parents --}}
        @if(isset($invoices))
          @include('dashboard.partials.invoice_table', ['invoices' => $invoices])
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
