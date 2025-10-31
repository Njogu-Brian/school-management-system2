{{-- resources/views/dashboard/transport.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-xxl">
  <h2 class="mb-3">Transport Dashboard</h2>

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
@endsection
