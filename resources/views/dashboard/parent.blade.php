{{-- resources/views/dashboard/parent.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-xxl">
  <h2 class="mb-3">Parent Dashboard</h2>

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
@endsection
