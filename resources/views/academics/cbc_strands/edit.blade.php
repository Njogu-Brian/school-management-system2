@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit CBC Strand</h1>
        <a href="{{ route('academics.cbc-strands.show', $cbc_strand) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.cbc-strands.update', $cbc_strand) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $cbc_strand->code) }}" required maxlength="20">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Level <span class="text-danger">*</span></label>
                        <select name="level" class="form-select @error('level') is-invalid @enderror" required>
                            <option value="PP1" {{ old('level', $cbc_strand->level) == 'PP1' ? 'selected' : '' }}>PP1</option>
                            <option value="PP2" {{ old('level', $cbc_strand->level) == 'PP2' ? 'selected' : '' }}>PP2</option>
                            <option value="Grade 1" {{ old('level', $cbc_strand->level) == 'Grade 1' ? 'selected' : '' }}>Grade 1</option>
                            <option value="Grade 2" {{ old('level', $cbc_strand->level) == 'Grade 2' ? 'selected' : '' }}>Grade 2</option>
                            <option value="Grade 3" {{ old('level', $cbc_strand->level) == 'Grade 3' ? 'selected' : '' }}>Grade 3</option>
                            <option value="Grade 4" {{ old('level', $cbc_strand->level) == 'Grade 4' ? 'selected' : '' }}>Grade 4</option>
                            <option value="Grade 5" {{ old('level', $cbc_strand->level) == 'Grade 5' ? 'selected' : '' }}>Grade 5</option>
                            <option value="Grade 6" {{ old('level', $cbc_strand->level) == 'Grade 6' ? 'selected' : '' }}>Grade 6</option>
                            <option value="Grade 7" {{ old('level', $cbc_strand->level) == 'Grade 7' ? 'selected' : '' }}>Grade 7</option>
                            <option value="Grade 8" {{ old('level', $cbc_strand->level) == 'Grade 8' ? 'selected' : '' }}>Grade 8</option>
                            <option value="Grade 9" {{ old('level', $cbc_strand->level) == 'Grade 9' ? 'selected' : '' }}>Grade 9</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $cbc_strand->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Learning Area <span class="text-danger">*</span></label>
                    <input type="text" name="learning_area" class="form-control @error('learning_area') is-invalid @enderror" 
                           value="{{ old('learning_area', $cbc_strand->learning_area) }}" required>
                    @error('learning_area')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $cbc_strand->description) }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" 
                               value="{{ old('display_order', $cbc_strand->display_order) }}" min="0">
                    </div>

                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                   value="1" {{ old('is_active', $cbc_strand->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.cbc-strands.show', $cbc_strand) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Strand</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


