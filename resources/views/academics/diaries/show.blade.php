@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <a href="{{ route('academics.diaries.index') }}" class="btn btn-link text-decoration-none mb-3">
        <i class="bi bi-arrow-left"></i> Back to Diaries
    </a>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-2">{{ $diary->student->getNameAttribute() }}</h5>
                    <p class="text-muted mb-1">Admission: {{ $diary->student->admission_number }}</p>
                    <p class="text-muted mb-1">Classroom: {{ $diary->student->classroom->name ?? '—' }}</p>
                    <p class="text-muted mb-0">Guardian: {{ $diary->student->parent?->father_name ?? $diary->student->parent?->guardian_name ?? '—' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="card shadow-sm mb-4">
                <div class="card-body diary-thread" style="max-height: 480px; overflow-y:auto;">
                    @forelse($entries as $entry)
                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span class="fw-semibold">{{ $entry->author->name }}</span>
                                    <span class="badge bg-light text-dark text-capitalize ms-2">{{ $entry->author_type }}</span>
                                </div>
                                <small class="text-muted">{{ $entry->created_at->format('M d, Y H:i') }}</small>
                            </div>
                            <div class="mt-2">
                                {!! nl2br(e($entry->content)) !!}
                            </div>
                            @if($entry->attachments)
                                <div class="mt-2">
                                    @foreach($entry->attachments as $file)
                                        <a href="{{ asset('storage/'.$file) }}" target="_blank" class="d-block">
                                            <i class="bi bi-paperclip"></i> Attachment {{ $loop->iteration }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <hr>
                        </div>
                    @empty
                        <p class="text-center text-muted my-5">No entries yet. Start the conversation below.</p>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('academics.diaries.entries.store', $diary) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">New Entry</label>
                            <textarea name="content" class="form-control" rows="4" placeholder="Share updates, feedback, or reminders..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                            <small class="text-muted">Optional. You can attach photos, PDFs, or documents (max 10MB each).</small>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Post Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
