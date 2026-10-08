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
        <h1 class="mb-1">Assign Stream</h1>
        <p class="text-muted mb-0">Choose an existing stream and the class it belongs to. This does not create a new class.</p>
      </div>
      <a href="{{ route('academics.streams.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form action="{{ route('academics.streams.store') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Existing stream</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Stream <span class="text-danger">*</span></label>
            <select name="stream_id" class="form-select" required>
              <option value="">-- Select stream --</option>
              @foreach ($catalogStreams as $stream)
                <option value="{{ $stream->id }}" @selected(old('stream_id') == $stream->id)>{{ $stream->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Love and Peace are the school streams. Pick one of them.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Class <span class="text-danger">*</span></label>
            <select name="classroom_id" class="form-select" required>
              <option value="">-- Select class --</option>
              @foreach ($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('classroom_id', request('classroom_id')) == $classroom->id)>{{ $classroom->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">The class must already exist. Assigning a stream does not create one.</small>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.streams.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Assign Stream</button>
      </div>
    </form>
  </div>
</div>
@endsection
