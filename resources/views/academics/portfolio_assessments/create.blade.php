@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Portfolio Assessments</div>
        <h1 class="mb-1">Create Portfolio Assessment</h1>
        <p class="text-muted mb-0">Record project or practical assessments for students.</p>
      </div>
      <a href="{{ route('academics.portfolio-assessments.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.portfolio-assessments.store') }}" method="POST" class="row g-3">
          @csrf

          <div class="col-md-6">
            <label class="form-label">Student <span class="text-danger">*</span></label>
            <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
              <option value="">Select Student</option>
              @foreach($students as $student)
                <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name }} - {{ $student->admission_number }}</option>
              @endforeach
            </select>
            @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Subject <span class="text-danger">*</span></label>
            <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
              <option value="">Select Subject</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Classroom <span class="text-danger">*</span></label>
            <select name="classroom_id" class="form-select @error('classroom_id') is-invalid @enderror" required>
              <option value="">Select Classroom</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ old('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
            @error('classroom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
              <option value="">Select Year</option>
              @foreach($years as $year)
                <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYearId ?? null) == $year->id ? 'selected' : '' }}>{{ $year->year }}</option>
              @endforeach
            </select>
            @error('academic_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4">
            <label class="form-label">Term <span class="text-danger">*</span></label>
            <select name="term_id" class="form-select @error('term_id') is-invalid @enderror" required>
              <option value="">Select Term</option>
              @foreach($terms as $term)
                <option value="{{ $term->id }}" {{ old('term_id', $currentTermId ?? null) == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
              @endforeach
            </select>
            @error('term_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Portfolio Type <span class="text-danger">*</span></label>
            <select name="portfolio_type" class="form-select @error('portfolio_type') is-invalid @enderror" required>
              <option value="project" {{ old('portfolio_type') == 'project' ? 'selected' : '' }}>Project</option>
              <option value="practical" {{ old('portfolio_type') == 'practical' ? 'selected' : '' }}>Practical</option>
              <option value="creative" {{ old('portfolio_type') == 'creative' ? 'selected' : '' }}>Creative</option>
              <option value="research" {{ old('portfolio_type') == 'research' ? 'selected' : '' }}>Research</option>
              <option value="group_work" {{ old('portfolio_type') == 'group_work' ? 'selected' : '' }}>Group Work</option>
              <option value="other" {{ old('portfolio_type') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('portfolio_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="submitted" {{ old('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
              <option value="assessed" {{ old('status') == 'assessed' ? 'selected' : '' }}>Assessed</option>
              <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Total Score</label>
            <input type="number" name="total_score" class="form-control" value="{{ old('total_score') }}" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-6">
            <label class="form-label">Performance Level</label>
            <select name="performance_level_id" class="form-select">
              <option value="">Select Level</option>
              @foreach($performanceLevels as $level)
                <option value="{{ $level->id }}" {{ old('performance_level_id') == $level->id ? 'selected' : '' }}>{{ $level->code }} - {{ $level->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Assessment Date</label>
            <input type="date" name="assessment_date" class="form-control" value="{{ old('assessment_date') }}">
          </div>

          <div class="col-12">
            <label class="form-label">Feedback</label>
            <textarea name="feedback" class="form-control" rows="3">{{ old('feedback') }}</textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.portfolio-assessments.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Create Assessment</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
