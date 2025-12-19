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
        <h1 class="mb-1">Edit Portfolio Assessment</h1>
        <p class="text-muted mb-0">Update status, scores, and feedback.</p>
      </div>
      <a href="{{ route('academics.portfolio-assessments.show', $portfolio_assessment) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.portfolio-assessments.update', $portfolio_assessment) }}" method="POST" class="row g-3">
          @csrf
          @method('PUT')

          <div class="col-md-6">
            <label class="form-label">Portfolio Type <span class="text-danger">*</span></label>
            <select name="portfolio_type" class="form-select @error('portfolio_type') is-invalid @enderror" required>
              <option value="project" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'project' ? 'selected' : '' }}>Project</option>
              <option value="practical" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'practical' ? 'selected' : '' }}>Practical</option>
              <option value="creative" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'creative' ? 'selected' : '' }}>Creative</option>
              <option value="research" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'research' ? 'selected' : '' }}>Research</option>
              <option value="group_work" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'group_work' ? 'selected' : '' }}>Group Work</option>
              <option value="other" {{ old('portfolio_type', $portfolio_assessment->portfolio_type) == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            @error('portfolio_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Status <span class="text-danger">*</span></label>
            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
              <option value="draft" {{ old('status', $portfolio_assessment->status) == 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="submitted" {{ old('status', $portfolio_assessment->status) == 'submitted' ? 'selected' : '' }}>Submitted</option>
              <option value="assessed" {{ old('status', $portfolio_assessment->status) == 'assessed' ? 'selected' : '' }}>Assessed</option>
              <option value="published" {{ old('status', $portfolio_assessment->status) == 'published' ? 'selected' : '' }}>Published</option>
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $portfolio_assessment->title) }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $portfolio_assessment->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-6">
            <label class="form-label">Total Score</label>
            <input type="number" name="total_score" class="form-control" value="{{ old('total_score', $portfolio_assessment->total_score) }}" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-6">
            <label class="form-label">Performance Level</label>
            <select name="performance_level_id" class="form-select">
              <option value="">Select Level</option>
              @foreach($performanceLevels as $level)
                <option value="{{ $level->id }}" {{ old('performance_level_id', $portfolio_assessment->performance_level_id) == $level->id ? 'selected' : '' }}>{{ $level->code }} - {{ $level->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Assessment Date</label>
            <input type="date" name="assessment_date" class="form-control" value="{{ old('assessment_date', $portfolio_assessment->assessment_date?->format('Y-m-d')) }}">
          </div>

          <div class="col-12">
            <label class="form-label">Feedback</label>
            <textarea name="feedback" class="form-control" rows="3">{{ old('feedback', $portfolio_assessment->feedback) }}</textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.portfolio-assessments.show', $portfolio_assessment) }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Update Assessment</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
