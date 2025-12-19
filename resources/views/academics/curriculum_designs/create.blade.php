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
        <h1 class="mb-1">Upload Curriculum Design</h1>
        <p class="text-muted mb-0">Upload a PDF to auto-extract learning areas, strands, and competencies.</p>
      </div>
      <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-upload"></i><h5 class="mb-0">Upload PDF Curriculum Design</h5></div>
          <div class="card-body">
            <form action="{{ route('academics.curriculum-designs.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
              @csrf
              <div class="col-12">
                <label class="form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required placeholder="e.g., Grade 4 Mathematics Curriculum Design">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="col-12">
                <div class="alert alert-soft alert-info border-0 mb-2"><i class="bi bi-magic"></i> Subject and class levels are detected automatically from the PDF.</div>
              </div>

              <div class="col-12">
                <label class="form-label">PDF File <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Max size: {{ number_format(config('curriculum_ai.pdf.max_file_size', 52428800) / 1024 / 1024) }}MB. Max pages: {{ config('curriculum_ai.pdf.max_pages', 500) }}.</small>
              </div>

              <div class="col-12">
                <div class="alert alert-soft alert-info border-0"><i class="bi bi-info-circle"></i> The PDF will process automatically after upload; large files may take several minutes. You'll be notified when complete.</div>
              </div>

              <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('academics.curriculum-designs.index') }}" class="btn btn-ghost-strong">Cancel</a>
                <button type="submit" class="btn btn-settings-primary"><i class="bi bi-upload"></i> Upload & Process</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Upload Guidelines</h5></div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li class="mb-2"><i class="bi bi-check-circle text-success"></i> PDF with clear headings</li>
              <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Include Learning Areas, Strands, Substrands, Competencies</li>
              <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Scanned PDFs use OCR (may be slower)</li>
              <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Review extracted data after processing</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
