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
        <h1 class="mb-1">{{ $curriculumDesign->title }}</h1>
        <p class="text-muted mb-0">File details, extraction status, and structure.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        @if($curriculumDesign->status === 'processed')
        <a href="{{ route('academics.curriculum-designs.review', $curriculumDesign) }}" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Review Extraction</a>
        @endif
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Basic Information</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Title:</dt><dd class="col-sm-9">{{ $curriculumDesign->title }}</dd>
              <dt class="col-sm-3">Subject:</dt><dd class="col-sm-9">{{ $curriculumDesign->subject->name ?? 'N/A' }}</dd>
              <dt class="col-sm-3">Class Level:</dt><dd class="col-sm-9">{{ $curriculumDesign->class_level ?? 'N/A' }}</dd>
              <dt class="col-sm-3">Status:</dt><dd class="col-sm-9"><span class="pill-badge pill-{{ $curriculumDesign->status === 'processed' ? 'success' : ($curriculumDesign->status === 'processing' ? 'warning' : 'danger') }}">{{ ucfirst($curriculumDesign->status) }}</span></dd>
              @if($curriculumDesign->status === 'processing')
              <dt class="col-sm-3">Progress:</dt>
              <dd class="col-sm-9">
                <div id="parsing-progress-container" class="mt-2">
                  <div class="d-flex justify-content-between align-items-center mb-2"><span id="progress-message" class="text-muted small">Initializing...</span><span id="progress-percentage" class="text-muted small">0%</span></div>
                  <div class="progress" style="height: 10px;"><div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div></div>
                  <div class="mt-1"><small class="text-muted">Pages: <span id="progress-pages">0</span> / <span id="progress-total">{{ $curriculumDesign->pages ?? 0 }}</span></small></div>
                </div>
              </dd>
              @endif
              <dt class="col-sm-3">Pages:</dt><dd class="col-sm-9">{{ $curriculumDesign->pages }}</dd>
              <dt class="col-sm-3">Uploaded By:</dt><dd class="col-sm-9">{{ $curriculumDesign->uploader->name ?? 'N/A' }}</dd>
              <dt class="col-sm-3">Uploaded At:</dt><dd class="col-sm-9">{{ $curriculumDesign->created_at->format('F d, Y H:i') }}</dd>
            </dl>
            @if($curriculumDesign->status === 'failed' && $curriculumDesign->error_notes)
              <div class="alert alert-danger mt-3"><strong>Error:</strong> {{ $curriculumDesign->error_notes }}</div>
            @endif
          </div>
        </div>

        @if($curriculumDesign->status === 'processed')
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-diagram-3"></i><h5 class="mb-0">Extracted Data Summary</h5></div>
          <div class="card-body">
            <div class="row text-center g-3">
              <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary">{{ $curriculumDesign->learning_areas_count ?? 0 }}</div><div class="stat-label">Learning Areas</div></div></div>
              <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary">{{ $curriculumDesign->strands_count ?? 0 }}</div><div class="stat-label">Strands</div></div></div>
              <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary">{{ $curriculumDesign->pages_count ?? 0 }}</div><div class="stat-label">Pages Processed</div></div></div>
              <div class="col-md-3"><div class="stat-card"><div class="stat-value text-primary">{{ $curriculumDesign->embeddings_count ?? 0 }}</div><div class="stat-label">Embeddings</div></div></div>
            </div>
          </div>
        </div>

        @if($curriculumDesign->learningAreas->count() > 0)
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-list-nested"></i><h5 class="mb-0">Extracted Structure</h5></div>
          <div class="card-body">
            @foreach($curriculumDesign->learningAreas as $learningArea)
              <div class="mb-4">
                <h6 class="text-primary"><i class="bi bi-folder"></i> {{ $learningArea->name }} ({{ $learningArea->code }})</h6>
                <p class="text-muted small mb-2">{{ $learningArea->description }}</p>
                @foreach($learningArea->strands as $strand)
                  <div class="ms-3 mb-2">
                    <strong class="text-secondary"><i class="bi bi-folder2"></i> Strand: {{ $strand->name }}</strong>
                    <small class="text-muted ms-1">({{ $strand->code }})</small>
                    <p class="text-muted small mb-1">{{ $strand->description }}</p>
                    @foreach($strand->substrands as $substrand)
                      <div class="ms-3 mb-1">
                        <small class="text-muted"><i class="bi bi-file-text"></i> {{ $substrand->name }} ({{ $substrand->code }}) — {{ $substrand->competencies->count() }} competencies</small>
                      </div>
                    @endforeach
                  </div>
                @endforeach
              </div>
            @endforeach
          </div>
        </div>
        @endif
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-gear"></i><h5 class="mb-0">Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @can('curriculum_designs.edit', $curriculumDesign)
            <a href="{{ route('academics.curriculum-designs.edit', $curriculumDesign) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit Metadata</a>
            @endcan
            @if($curriculumDesign->status === 'processed' || $curriculumDesign->status === 'failed')
            <form action="{{ route('academics.curriculum-designs.reprocess', $curriculumDesign) }}" method="POST" class="p-0">
              @csrf
              <button type="submit" class="btn btn-ghost-strong text-warning" onclick="return confirm('Reprocess this curriculum design? This may take several minutes.');"><i class="bi bi-arrow-clockwise"></i> Reprocess</button>
            </form>
            @endif
            @can('curriculum_designs.delete', $curriculumDesign)
            <form action="{{ route('academics.curriculum-designs.destroy', $curriculumDesign) }}" method="POST" onsubmit="return confirm('Delete this curriculum design?');" class="p-0">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-ghost-strong text-danger"><i class="bi bi-trash"></i> Delete</button>
            </form>
            @endcan
          </div>
        </div>

        @if($curriculumDesign->status === 'processing')
        <div class="settings-card border-warning-subtle mb-3">
          <div class="card-body text-center">
            <div class="spinner-border text-warning" role="status"><span class="visually-hidden">Processing...</span></div>
            <p class="mt-3 mb-0">Processing in progress...</p>
            <small class="text-muted">Progress is shown above</small>
          </div>
        </div>
        @endif

        @if($curriculumDesign->audits->count() > 0)
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-clock-history"></i><h5 class="mb-0">Recent Activity</h5></div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              @foreach($curriculumDesign->audits->take(5) as $audit)
                <li class="mb-2 pb-2 border-bottom">
                  <small class="text-muted">{{ $audit->created_at->diffForHumans() }}</small><br>
                  <strong>{{ $audit->action }}</strong>
                  @if($audit->user) by {{ $audit->user->name }} @endif
                </li>
              @endforeach
            </ul>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

@if($curriculumDesign->status === 'processing')
<script>
(function() {
  const progressUrl='{{ route("academics.curriculum-designs.progress", $curriculumDesign) }}';
  const progressBar=document.getElementById('progress-bar');
  const progressPercentage=document.getElementById('progress-percentage');
  const progressMessage=document.getElementById('progress-message');
  const progressPages=document.getElementById('progress-pages');
  const progressTotal=document.getElementById('progress-total');

  function updateProgress(){
    fetch(progressUrl,{headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(r=>r.json())
      .then(data=>{
        const pct=data.percentage ?? 0;
        progressBar.style.width=`${pct}%`;
        progressBar.setAttribute('aria-valuenow', pct);
        progressPercentage.textContent=`${pct}%`;
        progressMessage.textContent=data.message ?? 'Processing...';
        progressPages.textContent=data.pages_processed ?? 0;
      })
      .catch(()=>{});
  }
  updateProgress();
  setInterval(updateProgress, 3000);
})();
</script>
@endif
@endsection
