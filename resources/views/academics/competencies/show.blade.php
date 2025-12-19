@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Competencies</div>
        <h1 class="mb-1">{{ $competency->name }}</h1>
        <p class="text-muted mb-0">Competency details and related strands/substrands.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        @can('competencies.edit')
        <a href="{{ route('academics.competencies.edit', $competency) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        <a href="{{ route('academics.competencies.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Competency Details</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><strong>{{ $competency->code }}</strong></dd>
              <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $competency->name }}</dd>
              <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $competency->description ?? '-' }}</dd>
              <dt class="col-sm-3">Learning Area</dt><dd class="col-sm-9">@if($competency->substrand && $competency->substrand->strand && $competency->substrand->strand->learningArea)<span class="pill-badge pill-info">{{ $competency->substrand->strand->learningArea->name }}</span>@else<span class="text-muted">-</span>@endif</dd>
              <dt class="col-sm-3">Strand</dt><dd class="col-sm-9">{{ $competency->substrand->strand->name ?? '-' }}</dd>
              <dt class="col-sm-3">Substrand</dt><dd class="col-sm-9">{{ $competency->substrand->name ?? '-' }}</dd>
              <dt class="col-sm-3">Level</dt><dd class="col-sm-9">@if($competency->competency_level)<span class="pill-badge pill-secondary">{{ $competency->competency_level }}</span>@else<span class="text-muted">-</span>@endif</dd>
              <dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($competency->is_active)<span class="pill-badge pill-success">Active</span>@else<span class="pill-badge pill-danger">Inactive</span>@endif</dd>
            </dl>
          </div>
        </div>

        @if($competency->indicators && count($competency->indicators) > 0)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Indicators</h5></div>
          <div class="card-body">
            <ul class="mb-0">
              @foreach($competency->indicators as $indicator)
                <li>{{ is_array($indicator) ? ($indicator['text'] ?? '') : $indicator }}</li>
              @endforeach
            </ul>
          </div>
        </div>
        @endif

        @if($competency->assessment_criteria && count($competency->assessment_criteria) > 0)
        <div class="settings-card">
          <div class="card-header"><h5 class="mb-0">Assessment Criteria</h5></div>
          <div class="card-body">
            <ul class="mb-0">
              @foreach($competency->assessment_criteria as $criterion)
                <li>{{ is_array($criterion) ? ($criterion['text'] ?? '') : $criterion }}</li>
              @endforeach
            </ul>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge"></i><h5 class="mb-0">Quick Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @if($competency->substrand)
            <a href="{{ route('academics.cbc-substrands.show', $competency->substrand) }}" class="btn btn-ghost-strong"><i class="bi bi-eye"></i> View Substrand</a>
            @endif
            @if($competency->substrand && $competency->substrand->strand)
            <a href="{{ route('academics.cbc-strands.show', $competency->substrand->strand) }}" class="btn btn-ghost-strong"><i class="bi bi-eye"></i> View Strand</a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
