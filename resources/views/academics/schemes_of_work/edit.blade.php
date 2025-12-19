@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Schemes of Work</div>
        <h1 class="mb-1">Edit Scheme of Work</h1>
        <p class="text-muted mb-0">Update scheme details and strands coverage.</p>
      </div>
      <a href="{{ route('academics.schemes-of-work.show', $schemes_of_work) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.schemes-of-work.update', $schemes_of_work) }}" method="POST" class="row g-3">
          @csrf
          @method('PUT')

          <div class="col-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $schemes_of_work->title) }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $schemes_of_work->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="draft" {{ old('status', $schemes_of_work->status) == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="active" {{ old('status', $schemes_of_work->status) == 'active' ? 'selected' : '' }}>Active</option>
              <option value="completed" {{ old('status', $schemes_of_work->status) == 'completed' ? 'selected' : '' }}>Completed</option>
              <option value="archived" {{ old('status', $schemes_of_work->status) == 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">CBC Strands Coverage</label>
            <select name="strands_coverage[]" class="form-select" multiple>
              @foreach($strands as $strand)
                <option value="{{ $strand->id }}" {{ in_array($strand->id, old('strands_coverage', $schemes_of_work->strands_coverage ?? [])) ? 'selected' : '' }}>{{ $strand->name }} ({{ $strand->code }})</option>
              @endforeach
            </select>
          </div>

          <div class="col-12">
            <label class="form-label">General Remarks</label>
            <textarea name="general_remarks" class="form-control" rows="3">{{ old('general_remarks', $schemes_of_work->general_remarks) }}</textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.schemes-of-work.show', $schemes_of_work) }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Update Scheme</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
