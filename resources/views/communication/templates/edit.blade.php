@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Communication Template</h4>

    <form action="{{ route('communication-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" value="{{ old('code',$template->code) }}" class="form-control" required>
            <small class="text-muted">Unique identifier used across modules.</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" value="{{ old('title',$template->title) }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Type *</label>
            <select name="type" class="form-control" required>
                <option value="email" @selected($template->type==='email')>Email</option>
                <option value="sms" @selected($template->type==='sms')>SMS</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Subject (Email)</label>
            <input type="text" name="subject" value="{{ old('subject',$template->subject) }}" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Attachment (Email)</label>
            <input type="file" name="attachment" class="form-control">
            @if($template->attachment)
                <p class="mt-1">Current: <a href="{{ asset('storage/'.$template->attachment) }}" target="_blank">View</a></p>
            @endif
        </div>

        <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea name="content" rows="10" class="form-control rich-text" required>{{ old('content',$template->content) }}</textarea>
        </div>

        <button class="btn btn-success">Update</button>
    </form>
</div>
@endsection
