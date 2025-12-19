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
        <h1 class="mb-1">{{ $portfolio_assessment->title }}</h1>
        <p class="text-muted mb-0">Assessment details, scores, and feedback.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.portfolio-assessments.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.portfolio-assessments.edit', $portfolio_assessment) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Portfolio Assessment Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Student</th><td>{{ $portfolio_assessment->student->full_name }}</td></tr>
              <tr><th>Subject</th><td>{{ $portfolio_assessment->subject->name }}</td></tr>
              <tr><th>Classroom</th><td>{{ $portfolio_assessment->classroom->name }}</td></tr>
              <tr><th>Portfolio Type</th><td><span class="pill-badge pill-info">{{ ucfirst($portfolio_assessment->portfolio_type) }}</span></td></tr>
              <tr><th>Status</th><td><span class="pill-badge pill-{{ $portfolio_assessment->status == 'assessed' ? 'success' : ($portfolio_assessment->status == 'published' ? 'primary' : 'warning') }}">{{ ucfirst($portfolio_assessment->status) }}</span></td></tr>
              @if($portfolio_assessment->total_score)<tr><th>Total Score</th><td><strong>{{ $portfolio_assessment->total_score }}/100</strong></td></tr>@endif
              @if($portfolio_assessment->performanceLevel)<tr><th>Performance Level</th><td><span class="pill-badge pill-success">{{ $portfolio_assessment->performanceLevel->code }} - {{ $portfolio_assessment->performanceLevel->name }}</span></td></tr>@endif
              @if($portfolio_assessment->assessment_date)<tr><th>Assessment Date</th><td>{{ $portfolio_assessment->assessment_date->format('d M Y') }}</td></tr>@endif
            </table>
          </div>
        </div>

        @if($portfolio_assessment->description)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Description</h5></div>
          <div class="card-body"><p class="mb-0">{{ $portfolio_assessment->description }}</p></div>
        </div>
        @endif

        @if($portfolio_assessment->feedback)
        <div class="settings-card">
          <div class="card-header"><h5 class="mb-0">Feedback</h5></div>
          <div class="card-body"><p class="mb-0">{{ $portfolio_assessment->feedback }}</p></div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Information</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Academic Year:</strong> {{ $portfolio_assessment->academicYear->year }}</small>
            <small class="text-muted d-block mb-1"><strong>Term:</strong> {{ $portfolio_assessment->term->name }}</small>
            @if($portfolio_assessment->assessor)
              <small class="text-muted d-block mb-1"><strong>Assessed by:</strong> {{ $portfolio_assessment->assessor->full_name }}</small>
            @endif
            <small class="text-muted d-block"><strong>Created:</strong> {{ $portfolio_assessment->created_at->format('d M Y') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
