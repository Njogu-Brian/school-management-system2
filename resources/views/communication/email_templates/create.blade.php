@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Create Email Template</h4>
    <form action="{{ route('email-templates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" class="form-control" value="{{ old('code', $smsTemplate->code ?? $emailTemplate->code ?? '') }}" required>
            <small class="text-muted">Use a unique identifier like: welcome_staff, absent_notification</small>
        </div>
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Attachment</label>
            <input type="file" name="attachment" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea name="message" class="form-control rich-text" rows="10" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
