@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $curriculumDesign->title }}</h1>
        <div>
            <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @if($curriculumDesign->status === 'processed')
                <a href="{{ route('academics.curriculum-designs.review', $curriculumDesign) }}" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Review Extraction
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Title:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->title }}</dd>

                        <dt class="col-sm-3">Subject:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->subject->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Class Level:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->class_level ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            @if($curriculumDesign->status === 'processed')
                                <span class="badge bg-success">Processed</span>
                            @elseif($curriculumDesign->status === 'processing')
                                <span class="badge bg-warning">Processing</span>
                            @else
                                <span class="badge bg-danger">Failed</span>
                            @endif
                        </dd>

                        @if($curriculumDesign->status === 'processing')
                        <dt class="col-sm-3">Progress:</dt>
                        <dd class="col-sm-9">
                            <div id="parsing-progress-container" class="mt-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span id="progress-message" class="text-muted small">Initializing...</span>
                                    <span id="progress-percentage" class="text-muted small">0%</span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         style="width: 0%" 
                                         aria-valuenow="0" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <span id="progress-text" class="small">0%</span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Pages: <span id="progress-pages">0</span> / <span id="progress-total">{{ $curriculumDesign->pages ?? 0 }}</span>
                                    </small>
                                </div>
                            </div>
                        </dd>
                        @endif

                        <dt class="col-sm-3">Pages:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->pages }}</dd>

                        <dt class="col-sm-3">Uploaded By:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->uploader->name ?? 'N/A' }}</dd>

                        <dt class="col-sm-3">Uploaded At:</dt>
                        <dd class="col-sm-9">{{ $curriculumDesign->created_at->format('F d, Y H:i') }}</dd>
                    </dl>

                    @if($curriculumDesign->status === 'failed' && $curriculumDesign->error_notes)
                        <div class="alert alert-danger mt-3">
                            <strong>Error:</strong> {{ $curriculumDesign->error_notes }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Extracted Data Summary -->
            @if($curriculumDesign->status === 'processed')
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Extracted Data Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0 text-primary">{{ $curriculumDesign->learning_areas_count ?? 0 }}</h3>
                                    <small class="text-muted">Learning Areas</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0 text-primary">{{ $curriculumDesign->strands_count ?? 0 }}</h3>
                                    <small class="text-muted">Strands</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0 text-primary">{{ $curriculumDesign->pages_count ?? 0 }}</h3>
                                    <small class="text-muted">Pages Processed</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0 text-primary">{{ $curriculumDesign->embeddings_count ?? 0 }}</h3>
                                    <small class="text-muted">Embeddings</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Learning Areas Tree -->
                @if($curriculumDesign->learningAreas->count() > 0)
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-list-nested"></i> Extracted Structure</h5>
                        </div>
                        <div class="card-body">
                            @foreach($curriculumDesign->learningAreas as $learningArea)
                                <div class="mb-4">
                                    <h6 class="text-primary">
                                        <i class="bi bi-folder"></i> {{ $learningArea->name }} ({{ $learningArea->code }})
                                    </h6>
                                    @foreach($learningArea->strands as $strand)
                                        <div class="ms-3 mb-2">
                                            <strong class="text-secondary">
                                                <i class="bi bi-folder2"></i> Strand: {{ $strand->name }}
                                            </strong>
                                            @foreach($strand->substrands as $substrand)
                                                <div class="ms-3 mb-1">
                                                    <small class="text-muted">
                                                        <i class="bi bi-file-text"></i> {{ $substrand->name }}
                                                        ({{ $substrand->competencies->count() }} competencies)
                                                    </small>
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
            <!-- Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Actions</h5>
                </div>
                <div class="card-body">
                    @can('curriculum_designs.edit', $curriculumDesign)
                        <a href="{{ route('academics.curriculum-designs.edit', $curriculumDesign) }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit Metadata
                        </a>
                    @endcan

                    @if($curriculumDesign->status === 'processed' || $curriculumDesign->status === 'failed')
                        <form action="{{ route('academics.curriculum-designs.reprocess', $curriculumDesign) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Reprocess this curriculum design? This may take several minutes.');">
                                <i class="bi bi-arrow-clockwise"></i> Reprocess
                            </button>
                        </form>
                    @endif

                    @can('curriculum_designs.delete', $curriculumDesign)
                        <form action="{{ route('academics.curriculum-designs.destroy', $curriculumDesign) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this curriculum design?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <!-- Processing Status -->
            @if($curriculumDesign->status === 'processing')
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-body text-center">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <p class="mt-3 mb-0">Processing in progress...</p>
                        <small class="text-muted">Progress is shown above</small>
                    </div>
                </div>
            @endif

            <!-- Audit Log -->
            @if($curriculumDesign->audits->count() > 0)
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach($curriculumDesign->audits->take(5) as $audit)
                                <li class="mb-2 pb-2 border-bottom">
                                    <small class="text-muted">{{ $audit->created_at->diffForHumans() }}</small><br>
                                    <strong>{{ $audit->action }}</strong>
                                    @if($audit->user)
                                        by {{ $audit->user->name }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($curriculumDesign->status === 'processing')
<script>
(function() {
    const progressUrl = '{{ route("academics.curriculum-designs.progress", $curriculumDesign) }}';
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressPercentage = document.getElementById('progress-percentage');
    const progressMessage = document.getElementById('progress-message');
    const progressPages = document.getElementById('progress-pages');
    const progressTotal = document.getElementById('progress-total');
    
    let pollInterval;
    let lastPercentage = 0;
    
    function updateProgress() {
        fetch(progressUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const percentage = Math.min(100, Math.max(0, data.percentage || 0));
            const pagesProcessed = data.pages_processed || 0;
            const totalPages = data.total_pages || {{ $curriculumDesign->pages ?? 0 }};
            const message = data.message || 'Processing...';
            const failed = data.failed || false;
            const status = data.status || 'processing';
            
            // Update progress bar
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressText.textContent = percentage + '%';
            progressPercentage.textContent = percentage + '%';
            
            // Update message
            progressMessage.textContent = message;
            
            // Update pages
            progressPages.textContent = pagesProcessed;
            progressTotal.textContent = totalPages;
            
            // Handle completion or failure
            if (status === 'processed' || percentage >= 100) {
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-success');
                clearInterval(pollInterval);
                // Reload page after 2 seconds to show final status
                setTimeout(() => location.reload(), 2000);
            } else if (failed || status === 'failed') {
                progressBar.classList.remove('progress-bar-animated');
                progressBar.classList.add('bg-danger');
                progressMessage.textContent = 'Failed: ' + message;
                clearInterval(pollInterval);
                // Reload page after 3 seconds to show error
                setTimeout(() => location.reload(), 3000);
            }
            
            lastPercentage = percentage;
        })
        .catch(error => {
            console.error('Error fetching progress:', error);
            // Continue polling even on error
        });
    }
    
    // Start polling immediately, then every 2 seconds
    updateProgress();
    pollInterval = setInterval(updateProgress, 2000);
    
    // Stop polling if page is hidden (browser tab)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(pollInterval);
        } else {
            pollInterval = setInterval(updateProgress, 2000);
        }
    });
})();
</script>
@endif

@endsection

