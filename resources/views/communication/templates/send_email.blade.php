@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Send Email via Template',
            'icon' => 'bi bi-envelope-paper',
            'subtitle' => $template->title ?? 'Email Template'
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('communication.templates.email.send', $template) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Recipients</label>
                            <select name="recipients[]" class="form-select" multiple required>
                                @foreach($recipients as $recipient)
                                    <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->email }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold CTRL/CMD to select multiple</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subject</label>
                            <input type="text" name="subject" class="form-control" required value="{{ old('subject', $template->title) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Preview</label>
                            <div class="p-3 bg-light rounded border">
                                {!! nl2br(e($template->content ?? '')) !!}
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('communication.templates.index') }}" class="btn btn-ghost-strong">Cancel</a>
                            <button class="btn btn-settings-primary"><i class="bi bi-send"></i> Send Email</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

