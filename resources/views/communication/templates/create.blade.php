@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Create Communication Template</h4>

    <form action="{{ route('communication-templates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="e.g. welcome_staff" required>
            <small class="text-muted">Unique identifier used across modules (attendance, admissions, finance, etc.)</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" value="{{ old('title') }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Type *</label>
            <select name="type" class="form-control" required>
                <option value="email" {{ old('type')==='email'?'selected':'' }}>Email</option>
                <option value="sms" {{ old('type')==='sms'?'selected':'' }}>SMS</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Subject (Email)</label>
            <input type="text" name="subject" value="{{ old('subject') }}" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Attachment (Email)</label>
            <input type="file" name="attachment" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Message *</label>
            <textarea name="content" rows="10" class="form-control rich-text" required>{{ old('content') }}</textarea>
        </div>

        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
