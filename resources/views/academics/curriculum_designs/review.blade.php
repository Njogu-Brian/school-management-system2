@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Review Extracted Data - {{ $curriculumDesign->title }}</h1>
        <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        Review the extracted curriculum data below. You can edit, accept, or reject items. 
        Changes will be saved to the database.
    </div>

    @if($curriculumDesign->learningAreas->count() > 0)
        <form id="reviewForm" method="POST" action="#">
            @csrf
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Extracted Curriculum Structure</h5>
                    <div>
                        <button type="button" class="btn btn-light btn-sm" onclick="expandAll()">
                            <i class="bi bi-arrows-expand"></i> Expand All
                        </button>
                        <button type="button" class="btn btn-light btn-sm" onclick="collapseAll()">
                            <i class="bi bi-arrows-collapse"></i> Collapse All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @foreach($curriculumDesign->learningAreas as $learningArea)
                        <div class="mb-4 border rounded p-3">
                            <h5 class="text-primary">
                                <i class="bi bi-folder"></i> Learning Area: {{ $learningArea->name }} 
                                <small class="text-muted">({{ $learningArea->code }})</small>
                            </h5>
                            <p class="text-muted">{{ $learningArea->description }}</p>

                            @foreach($learningArea->strands as $strand)
                                <div class="ms-3 mb-3 border-start border-2 ps-3">
                                    <h6 class="text-secondary">
                                        <i class="bi bi-folder2"></i> Strand: {{ $strand->name }}
                                        <small class="text-muted">({{ $strand->code }})</small>
                                    </h6>
                                    <p class="text-muted small">{{ $strand->description }}</p>

                                    @foreach($strand->substrands as $substrand)
                                        <div class="ms-3 mb-2 border-start border-1 ps-2">
                                            <h6 class="text-dark">
                                                <i class="bi bi-file-text"></i> Substrand: {{ $substrand->name }}
                                                <small class="text-muted">({{ $substrand->code }})</small>
                                            </h6>
                                            <p class="text-muted small">{{ $substrand->description }}</p>

                                            <!-- Competencies -->
                                            @if($substrand->competencies->count() > 0)
                                                <div class="ms-3 mb-2">
                                                    <strong class="small">Competencies:</strong>
                                                    <ul class="list-unstyled ms-2">
                                                        @foreach($substrand->competencies as $competency)
                                                            <li class="small">
                                                                <i class="bi bi-check-circle text-success"></i> 
                                                                {{ $competency->name ?? $competency->description }}
                                                                <small class="text-muted">({{ $competency->code }})</small>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <!-- Suggested Experiences -->
                                            @if($substrand->suggestedExperiences->count() > 0)
                                                <div class="ms-3 mb-2">
                                                    <strong class="small">Suggested Experiences:</strong>
                                                    <ul class="list-unstyled ms-2">
                                                        @foreach($substrand->suggestedExperiences as $experience)
                                                            <li class="small">
                                                                <i class="bi bi-lightbulb text-warning"></i> 
                                                                {{ Str::limit($experience->content, 100) }}
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif

                                            <!-- Rubrics -->
                                            @if($substrand->assessmentRubrics->count() > 0)
                                                <div class="ms-3 mb-2">
                                                    <strong class="small">Assessment Rubrics:</strong>
                                                    <span class="badge bg-info">{{ $substrand->assessmentRubrics->count() }} rubric(s)</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('academics.curriculum-designs.show', $curriculumDesign) }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-success" onclick="acceptAll()">
                            <i class="bi bi-check-circle"></i> Accept All
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            No data was extracted from this curriculum design. You may need to reprocess it.
        </div>
    @endif
</div>

<script>
function expandAll() {
    // Implementation for expanding all sections
    document.querySelectorAll('.collapse').forEach(el => {
        if (el.classList.contains('show')) return;
        const bsCollapse = new bootstrap.Collapse(el, { toggle: true });
    });
}

function collapseAll() {
    // Implementation for collapsing all sections
    document.querySelectorAll('.collapse.show').forEach(el => {
        const bsCollapse = new bootstrap.Collapse(el, { toggle: true });
    });
}

function acceptAll() {
    if (confirm('Accept all extracted data as-is?')) {
        // Submit form or make AJAX call
        document.getElementById('reviewForm').submit();
    }
}
</script>
@endsection

