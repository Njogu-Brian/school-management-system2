@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Email Template</h4>
    <form action="{{ route('email-templates.update', $emailTemplate->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $smsTemplate->code ?? $emailTemplate->code ?? '') }}" required>
            <small class="text-muted">Use a unique identifier like: welcome_staff, absent_notification</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" value="{{ $emailTemplate->title }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Attachment</label>
            <input type="file" name="attachment" class="form-control">
            @if($emailTemplate->attachment)
                <p>Current: <a href="{{ asset('storage/' . $emailTemplate->attachment) }}" target="_blank">View</a></p>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea name="message" class="form-control rich-text" rows="10" required>{{ $emailTemplate->message }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Update</button>
    </form>
</div>
@endsection
