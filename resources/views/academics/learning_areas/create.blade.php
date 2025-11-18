@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Learning Area</h1>
        <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.learning-areas.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code') }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">e.g., ENG, MATH, SCI, KIS</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="level_category" class="form-label">Level Category</label>
                        <select class="form-select @error('level_category') is-invalid @enderror" 
                                id="level_category" name="level_category">
                            <option value="">Select Category</option>
                            @foreach($levelCategories as $key => $category)
                                <option value="{{ $category }}" {{ old('level_category') == $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                        @error('level_category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label">Levels</label>
                        <div class="row">
                            @php
                                $selectedLevels = old('levels', []);
                            @endphp
                            @foreach($levels as $category => $levelList)
                                <div class="col-md-6 mb-2">
                                    <strong>{{ $category }}:</strong>
                                    @foreach($levelList as $level)
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="levels[]" value="{{ $level }}" 
                                                   id="level_{{ $level }}"
                                                   {{ in_array($level, $selectedLevels) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="level_{{ $level }}">
                                                {{ $level }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        @error('levels')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control @error('display_order') is-invalid @enderror" 
                               id="display_order" name="display_order" value="{{ old('display_order', 0) }}" min="0">
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_core" name="is_core" value="1" 
                                   {{ old('is_core', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_core">
                                Core Learning Area
                            </label>
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Create Learning Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

