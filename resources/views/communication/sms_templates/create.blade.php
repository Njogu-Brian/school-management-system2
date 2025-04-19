@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Create SMS Template</h4>
    <form action="{{ route('sms-templates.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. absent_notice" required>
            <small class="text-muted">Used to fetch template programmatically (e.g. welcome_staff)</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Message Content *</label>
            <textarea name="content" class="form-control" rows="6" maxlength="300" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
