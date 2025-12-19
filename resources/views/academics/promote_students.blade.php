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
        <h1 class="mb-1">Promote Students (Quick)</h1>
        <p class="text-muted mb-0">Select source and destination classes for a quick promotion.</p>
      </div>
      <a href="{{ route('academics.promotions.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form action="#" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Select Class to Promote From</label>
            <select class="form-select" name="current_class_id">
              <option value="1">Class A</option>
              <option value="2">Class B</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Select Class to Promote To</label>
            <select class="form-select" name="new_class_id">
              <option value="3">Class C</option>
              <option value="4">Class D</option>
            </select>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.promotions.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Promote Students</button>
      </div>
    </form>
  </div>
</div>
@endsection
