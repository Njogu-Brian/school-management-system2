@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · CBC Strands</div>
        <h1 class="mb-1">Create CBC Strand</h1>
        <p class="text-muted mb-0">Add code, level, learning area, and details.</p>
      </div>
      <a href="{{ route('academics.cbc-strands.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.cbc-strands.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="20">
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Level <span class="text-danger">*</span></label>
              <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                <option value="">Select Level</option>
                @foreach(['PP1','PP2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9'] as $lvl)
                  <option value="{{ $lvl }}" {{ old('level') == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                @endforeach
              </select>
              @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mt-3">
            <label class="form-label">Learning Area <span class="text-danger">*</span></label>
            <input type="text" name="learning_area" class="form-control @error('learning_area') is-invalid @enderror" value="{{ old('learning_area') }}" required>
            @error('learning_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}" min="0">
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.cbc-strands.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Create Strand</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
