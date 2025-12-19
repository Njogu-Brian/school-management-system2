@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Curriculum Designs</div>
        <h1 class="mb-1">Edit Curriculum Design</h1>
        <p class="text-muted mb-0">Update metadata for this design.</p>
      </div>
      <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.curriculum-designs.update', $curriculumDesign) }}" method="POST" class="row g-3">
          @csrf
          @method('PUT')

          <div class="col-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $curriculumDesign->title) }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror">
              <option value="">Select Subject (Optional)</option>
              @foreach($subjects ?? [] as $subject)
                <option value="{{ $subject->id }}" {{ old('subject_id', $curriculumDesign->subject_id) == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Class Level</label>
            <input type="text" name="class_level" class="form-control @error('class_level') is-invalid @enderror" value="{{ old('class_level', $curriculumDesign->class_level) }}">
            @error('class_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary"><i class="bi bi-save"></i> Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
