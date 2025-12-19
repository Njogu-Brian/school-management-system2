@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Send SMS via Template',
            'icon' => 'bi bi-chat-dots',
            'subtitle' => $template->title ?? 'SMS Template'
        ])

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('communication.templates.sms.send', $template) }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Recipients</label>
                            <select name="recipients[]" class="form-select" multiple required>
                                @foreach($recipients as $recipient)
                                    <option value="{{ $recipient->id }}">{{ $recipient->name }} ({{ $recipient->phone }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hold CTRL/CMD to select multiple</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sender ID</label>
                            <input type="text" name="sender_id" class="form-control" value="{{ old('sender_id', config('app.name')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Preview</label>
                            <div class="p-3 bg-light rounded border">
                                {!! nl2br(e($template->content ?? '')) !!}
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('communication.templates.index') }}" class="btn btn-ghost-strong">Cancel</a>
                            <button class="btn btn-settings-primary"><i class="bi bi-send"></i> Send SMS</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

