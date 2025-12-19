@extends('layouts.app')

@push('styles')
  @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Sections Management</h1>
        <p class="text-muted mb-0">Manage class sections and streams.</p>
      </div>
      <a href="#" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add New Section</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <p class="text-muted mb-0">Placeholder for sections management functionality.</p>
      </div>
    </div>
  </div>
</div>
@endsection
