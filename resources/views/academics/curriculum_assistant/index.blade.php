@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Curriculum AI Assistant</h1>
        <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Curriculum Designs
        </a>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Selection Panel -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Configuration</h5>
                </div>
                <div class="card-body">
                    <form id="assistantConfig">
                        <div class="mb-3">
                            <label class="form-label">Curriculum Design <span class="text-danger">*</span></label>
                            <select name="curriculum_design_id" id="curriculum_design_id" class="form-select" required>
                                <option value="">Select Curriculum Design</option>
                                @foreach($curriculumDesigns ?? [] as $design)
                                    <option value="{{ $design->id }}" 
                                            {{ $design->status === 'processed' ? '' : 'disabled' }}
                                            data-status="{{ $design->status }}">
                                        {{ $design->title }}
                                        @if($design->status !== 'processed')
                                            ({{ ucfirst($design->status) }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only processed curriculum designs are available</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Generation Type</label>
                            <select name="type" id="generation_type" class="form-select">
                                <option value="scheme">Scheme of Work</option>
                                <option value="lesson_plan">Lesson Plan</option>
                                <option value="assessment">Assessment Items</option>
                                <option value="report_card">Report Card Content</option>
                            </select>
                        </div>

                        <div id="contextFields">
                            <!-- Dynamic context fields based on type -->
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-outline-primary w-100 mb-2" onclick="setQuickQuery('Generate a 12-week scheme of work')">
                        <i class="bi bi-calendar"></i> 12-Week Scheme
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2" onclick="setQuickQuery('Create a lesson plan for this week')">
                        <i class="bi bi-journal-text"></i> This Week's Lesson
                    </button>
                    <button class="btn btn-outline-primary w-100 mb-2" onclick="setQuickQuery('Generate 10 assessment questions')">
                        <i class="bi bi-question-circle"></i> 10 Questions
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Chat/Query Interface -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Ask the Assistant</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Your Query</label>
                        <textarea id="queryInput" class="form-control" rows="4" 
                                  placeholder="e.g., Generate a 12-week scheme of work for Grade 4 Mathematics covering Strand 1 and Substrand 1.2"></textarea>
                    </div>
                    <button id="generateBtn" class="btn btn-primary w-100" onclick="generateContent()">
                        <i class="bi bi-magic"></i> Generate Content
                    </button>
                </div>
            </div>

            <!-- Results Panel -->
            <div id="resultsPanel" class="card shadow-sm" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Generated Content</h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyResults()">
                            <i class="bi bi-clipboard"></i> Copy
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="exportResults()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="resultsContent" class="p-3 bg-light rounded">
                        <!-- Results will be displayed here -->
                    </div>
                    
                    <!-- Citations -->
                    <div id="citationsSection" class="mt-3" style="display: none;">
                        <h6><i class="bi bi-book"></i> Sources</h6>
                        <div id="citationsList" class="small text-muted">
                            <!-- Citations will be displayed here -->
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-3 d-flex justify-content-end gap-2">
                        <button class="btn btn-secondary" onclick="regenerateContent()">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate
                        </button>
                        <button class="btn btn-success" onclick="acceptContent()">
                            <i class="bi bi-check-circle"></i> Accept & Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Generating...</span>
                </div>
                <p class="mt-3 text-muted">Generating content, please wait...</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentResults = null;

function setQuickQuery(query) {
    document.getElementById('queryInput').value = query;
}

function generateContent() {
    const curriculumDesignId = document.getElementById('curriculum_design_id').value;
    const type = document.getElementById('generation_type').value;
    const query = document.getElementById('queryInput').value;

    if (!curriculumDesignId || !query) {
        alert('Please select a curriculum design and enter a query.');
        return;
    }

    // Show loading
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('resultsPanel').style.display = 'none';
    document.getElementById('generateBtn').disabled = true;

    // Prepare context
    const context = {};
    // Add context fields based on type

    // Make API call
    fetch('{{ route("academics.curriculum-assistant.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            curriculum_design_id: curriculumDesignId,
            type: type,
            query: query,
            context: context
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('generateBtn').disabled = false;

        if (data.success) {
            currentResults = data;
            displayResults(data);
        } else {
            alert('Error: ' + (data.error || 'Failed to generate content'));
        }
    })
    .catch(error => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('generateBtn').disabled = false;
        alert('Error: ' + error.message);
    });
}

function displayResults(data) {
    const resultsContent = document.getElementById('resultsContent');
    const citationsSection = document.getElementById('citationsSection');
    const citationsList = document.getElementById('citationsList');

    // Display content
    if (data.content && typeof data.content === 'object') {
        resultsContent.innerHTML = '<pre>' + JSON.stringify(data.content, null, 2) + '</pre>';
    } else {
        resultsContent.innerHTML = '<pre>' + (data.raw_response || 'No content generated') + '</pre>';
    }

    // Display citations
    if (data.citations && data.citations.length > 0) {
        citationsList.innerHTML = data.citations.map((cite, idx) => 
            `<div class="mb-2">${idx + 1}. ${cite.text} ${cite.page ? '(Page ' + cite.page + ')' : ''}</div>`
        ).join('');
        citationsSection.style.display = 'block';
    }

    document.getElementById('resultsPanel').style.display = 'block';
}

function copyResults() {
    const text = document.getElementById('resultsContent').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    });
}

function exportResults() {
    // Implementation for exporting results
    alert('Export functionality to be implemented');
}

function regenerateContent() {
    generateContent();
}

function acceptContent() {
    if (confirm('Accept and save this generated content?')) {
        // Implementation for saving content
        alert('Save functionality to be implemented');
    }
}
</script>
@endsection

