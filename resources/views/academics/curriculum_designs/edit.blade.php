@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Curriculum Design</h1>
        <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Metadata</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('academics.curriculum-designs.update', $curriculumDesign) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $curriculumDesign->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror">
                                    <option value="">Select Subject (Optional)</option>
                                    @foreach($subjects ?? [] as $subject)
                                        <option value="{{ $subject->id }}" 
                                                {{ old('subject_id', $curriculumDesign->subject_id) == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subject_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Class Level</label>
                                <input type="text" name="class_level" class="form-control @error('class_level') is-invalid @enderror" 
                                       value="{{ old('class_level', $curriculumDesign->class_level) }}">
                                @error('class_level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

