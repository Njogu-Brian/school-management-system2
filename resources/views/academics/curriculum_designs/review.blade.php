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
        <h1 class="mb-1">Review Extracted Data</h1>
        <p class="text-muted mb-0">Review, edit, and accept extracted curriculum structure.</p>
      </div>
      <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="alert alert-soft alert-info border-0">
      <i class="bi bi-info-circle"></i> Review the extracted curriculum data below. You can edit, accept, or reject items. Changes will be saved to the database.
    </div>

    @if($curriculumDesign->learningAreas->count() > 0)
    <form id="reviewForm" method="POST" action="#">
      @csrf
      <div class="settings-card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2"><i class="bi bi-diagram-3"></i><h5 class="mb-0">Extracted Curriculum Structure</h5></div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-ghost-strong btn-sm" onclick="expandAll()"><i class="bi bi-arrows-expand"></i> Expand All</button>
            <button type="button" class="btn btn-ghost-strong btn-sm" onclick="collapseAll()"><i class="bi bi-arrows-collapse"></i> Collapse All</button>
          </div>
        </div>
        <div class="card-body">
          @foreach($curriculumDesign->learningAreas as $learningArea)
            <div class="mb-4 border rounded p-3">
              <h6 class="text-primary"><i class="bi bi-folder"></i> Learning Area: {{ $learningArea->name }} <small class="text-muted">({{ $learningArea->code }})</small></h6>
              <p class="text-muted small">{{ $learningArea->description }}</p>
              @foreach($learningArea->strands as $strand)
                <div class="ms-3 mb-3 border-start border-2 ps-3">
                  <h6 class="text-secondary"><i class="bi bi-folder2"></i> Strand: {{ $strand->name }} <small class="text-muted">({{ $strand->code }})</small></h6>
                  <p class="text-muted small">{{ $strand->description }}</p>
                  @foreach($strand->substrands as $substrand)
                    <div class="ms-3 mb-2 border-start border-1 ps-2">
                      <h6 class="text-dark"><i class="bi bi-file-text"></i> Substrand: {{ $substrand->name }} <small class="text-muted">({{ $substrand->code }})</small></h6>
                      <p class="text-muted small">{{ $substrand->description }}</p>
                      @if($substrand->competencies->count() > 0)
                        <div class="ms-3 mb-2">
                          <strong class="small">Competencies:</strong>
                          <ul class="list-unstyled ms-2 mb-0">
                            @foreach($substrand->competencies as $competency)
                              <li class="small"><i class="bi bi-check-circle text-success"></i> {{ $competency->name ?? $competency->description }} <small class="text-muted">({{ $competency->code }})</small></li>
                            @endforeach
                          </ul>
                        </div>
                      @endif
                      @if($substrand->suggestedExperiences->count() > 0)
                        <div class="ms-3 mb-2"><strong class="small">Suggested Experiences:</strong><ul class="list-unstyled ms-2 mb-0">@foreach($substrand->suggestedExperiences as $experience)<li class="small"><i class="bi bi-lightbulb text-warning"></i> {{ Str::limit($experience->content, 100) }}</li>@endforeach</ul></div>
                      @endif
                      @if($substrand->assessmentRubrics->count() > 0)
                        <div class="ms-3 mb-2"><strong class="small">Assessment Rubrics:</strong> <span class="pill-badge pill-info">{{ $substrand->assessmentRubrics->count() }} rubric(s)</span></div>
                      @endif
                    </div>
                  @endforeach
                </div>
              @endforeach
            </div>
          @endforeach
        </div>
      </div>

      <div class="settings-card">
        <div class="card-body d-flex justify-content-end gap-2">
          <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-ghost-strong">Cancel</a>
          <button type="button" class="btn btn-settings-primary" onclick="acceptAll()"><i class="bi bi-check-circle"></i> Accept All</button>
          <button type="submit" class="btn btn-settings-primary"><i class="bi bi-save"></i> Save Changes</button>
        </div>
      </div>
    </form>
    @else
      <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> No data was extracted from this curriculum design. You may need to reprocess it.</div>
    @endif
  </div>
</div>

@push('scripts')
<script>
function expandAll(){ document.querySelectorAll('.collapse').forEach(el=>{ if(!el.classList.contains('show')) new bootstrap.Collapse(el,{toggle:true});}); }
function collapseAll(){ document.querySelectorAll('.collapse.show').forEach(el=>{ new bootstrap.Collapse(el,{toggle:true});}); }
function acceptAll(){ if(confirm('Accept all extracted data as-is?')) document.getElementById('reviewForm').submit(); }
</script>
@endpush
@endsection
