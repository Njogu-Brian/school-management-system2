@extends('layouts.app')

@push('styles')
    @include('academics.diaries.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header">
      <div>
        <div class="crumb">
          <a href="{{ route('academics.diaries.index') }}">Digital Diaries</a> · Thread
        </div>
        <h1 class="mb-1">{{ $diary->student?->name ?? 'Diary #'.$diary->id }}</h1>
        <p class="mb-0">
          {{ $diary->student?->admission_number ?? '—' }}
          · {{ optional($diary->student?->classroom)->name ?? 'Unassigned' }}
        </p>
      </div>
      <div class="header-actions">
        <a href="{{ route('academics.diaries.index') }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    @if(! $diary->student)
      <div class="alert alert-warning">
        This diary is no longer linked to a student record. Posting is disabled.
      </div>
    @endif

    <div class="row g-3">
      <div class="col-lg-4">
        <div class="settings-card h-100">
          <div class="card-header"><h5 class="mb-0">Student</h5></div>
          <div class="card-body">
            <p class="mb-2"><span class="text-muted">Admission</span><br><strong>{{ $diary->student?->admission_number ?? '—' }}</strong></p>
            <p class="mb-2"><span class="text-muted">Classroom</span><br><strong>{{ optional($diary->student?->classroom)->name ?? '—' }}</strong></p>
            <p class="mb-0"><span class="text-muted">Guardian</span><br><strong>{{ $diary->student?->parent?->father_name ?? $diary->student?->parent?->guardian_name ?? '—' }}</strong></p>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Conversation</h5></div>
          <div class="card-body diary-thread" style="max-height: min(60vh, 520px); overflow-y: auto;">
            @forelse($entries as $entry)
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                  <div>
                    <span class="fw-semibold">{{ $entry->author->name ?? 'Unknown' }}</span>
                    <span class="pill-badge pill-muted text-capitalize ms-2">{{ $entry->author_type }}</span>
                  </div>
                  <small class="text-muted">{{ $entry->created_at->format('M d, Y H:i') }}</small>
                </div>
                <div class="mt-2">{!! nl2br(e($entry->content)) !!}</div>
                @if($entry->attachments)
                  <div class="mt-2 d-flex flex-column gap-1">
                    @foreach($entry->attachments as $file)
                      <a href="{{ asset('storage/'.$file) }}" target="_blank" rel="noopener" class="text-decoration-none">
                        <i class="bi bi-paperclip"></i> Attachment {{ $loop->iteration }}
                      </a>
                    @endforeach
                  </div>
                @endif
                @if(! $loop->last)<hr class="my-3">@endif
              </div>
            @empty
              <p class="text-center text-muted my-5">No entries yet. Start the conversation below.</p>
            @endforelse
          </div>
        </div>

        @if($diary->student)
          <div class="settings-card">
            <div class="card-body">
              <form action="{{ route('academics.diaries.entries.store', $diary) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-12">
                  <label class="form-label" for="entry_content">New Entry</label>
                  <textarea name="content" id="entry_content" class="form-control" rows="4" placeholder="Share updates, feedback, or reminders..." required></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label" for="entry_attachments">Attachments</label>
                  <input type="file" name="attachments[]" id="entry_attachments" class="form-control" multiple>
                  <small class="text-muted">Optional. Attach photos, PDFs, or documents (max 10MB each).</small>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                  <a href="{{ route('academics.diaries.index') }}" class="btn btn-ghost-strong">Cancel</a>
                  <button type="submit" class="btn btn-settings-primary"><i class="bi bi-send"></i> Post Entry</button>
                </div>
              </form>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
