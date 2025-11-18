@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Upload Curriculum Design</h1>
        <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-upload"></i> Upload PDF Curriculum Design</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('academics.curriculum-designs.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title') }}" required placeholder="e.g., Grade 4 Mathematics Curriculum Design">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-secondary">
                            <i class="bi bi-magic"></i>
                            Subject and class levels are now detected automatically from the PDF.
                            You no longer need to select them manually.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">PDF File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                   accept=".pdf" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                Maximum file size: {{ number_format(config('curriculum_ai.pdf.max_file_size', 52428800) / 1024 / 1024) }}MB. 
                                Maximum pages: {{ config('curriculum_ai.pdf.max_pages', 500) }}.
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Note:</strong> The PDF will be processed automatically after upload. 
                            Processing may take several minutes for large files. You'll be notified when processing is complete.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload & Process
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Upload Guidelines</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            PDF should be well-structured with clear headings
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Include Learning Areas, Strands, Substrands, and Competencies
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Scanned PDFs will use OCR (may take longer)
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> 
                            Review extracted data after processing
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

