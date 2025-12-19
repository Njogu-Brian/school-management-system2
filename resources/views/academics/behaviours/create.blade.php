@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Behaviour</div>
        <h1 class="mb-1">Add Behaviour Category</h1>
        <p class="text-muted mb-0">Create a category for behaviour records.</p>
      </div>
      <a href="{{ route('academics.behaviours.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form action="{{ route('academics.behaviours.store') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        @include('academics.behaviours.partials.form',['behaviour'=>null])
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.behaviours.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection
