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
        <h1 class="mb-1">Edit Stream</h1>
        <p class="text-muted mb-0">Update stream info and classroom assignments.</p>
      </div>
      <a href="{{ route('academics.streams.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form action="{{ route('academics.streams.update', $stream->id) }}" method="POST" class="settings-card">
      @csrf
      @method('PUT')
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-pencil"></i> Stream Information</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Stream Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $stream->name) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Primary Classroom <span class="text-danger">*</span></label>
            <select name="classroom_id" class="form-select" required>
              <option value="">-- Select Primary Classroom --</option>
              @foreach ($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', $stream->classroom_id) == $classroom->id)>{{ $classroom->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Select the primary classroom this stream belongs to</small>
          </div>
        </div>

        <div class="mt-3">
          <label class="form-label">Assign to Additional Classrooms</label>
          <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
            <div class="row g-2">
              @foreach ($classrooms as $classroom)
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" id="classroom_{{ $classroom->id }}"
                      {{ (old('classroom_ids') && in_array($classroom->id, old('classroom_ids'))) || (isset($assignedClassrooms) && in_array($classroom->id, $assignedClassrooms)) ? 'checked' : '' }}>
                    <label class="form-check-label" for="classroom_{{ $classroom->id }}">{{ $classroom->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
          <small class="text-muted">Optional: make this stream available in other classrooms.</small>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.streams.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Update Stream</button>
      </div>
    </form>
  </div>
</div>
@endsection
