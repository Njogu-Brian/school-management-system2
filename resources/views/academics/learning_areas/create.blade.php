@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Learning Areas</div>
        <h1 class="mb-1">Create Learning Area</h1>
        <p class="text-muted mb-0">Add code, levels, type, and status.</p>
      </div>
      <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.learning-areas.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}" required>
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <small class="text-muted">e.g., ENG, MATH, SCI, KIS</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Level Category</label>
              <select class="form-select @error('level_category') is-invalid @enderror" name="level_category">
                <option value="">Select Category</option>
                @foreach($levelCategories as $category)
                  <option value="{{ $category }}" {{ old('level_category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                @endforeach
              </select>
              @error('level_category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-8">
              <label class="form-label">Levels</label>
              <div class="row">
                @php $selectedLevels = old('levels', []); @endphp
                @foreach($levels as $category => $levelList)
                  <div class="col-md-6 mb-2">
                    <strong>{{ $category }}:</strong>
                    @foreach($levelList as $level)
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="levels[]" value="{{ $level }}" id="level_{{ $level }}" {{ in_array($level, $selectedLevels) ? 'checked' : '' }}>
                        <label class="form-check-label" for="level_{{ $level }}">{{ $level }}</label>
                      </div>
                    @endforeach
                  </div>
                @endforeach
              </div>
              @error('levels')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Display Order</label>
              <input type="number" class="form-control @error('display_order') is-invalid @enderror" name="display_order" value="{{ old('display_order', 0) }}" min="0">
              @error('display_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_core" name="is_core" value="1" {{ old('is_core', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_core">Core Learning Area</label>
              </div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary"><i class="bi bi-save"></i> Create Learning Area</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
