@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit SMS Template</h4>
    <form action="{{ route('sms-templates.update', $smsTemplate->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" value="{{ $smsTemplate->title }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Template Code *</label>
            <input type="text" name="code" class="form-control" value="{{ $smsTemplate->code }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Message Content *</label>
            <textarea name="message" class="form-control" rows="6" maxlength="300" required>{{ $smsTemplate->content }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Update</button>
    </form>
</div>
@endsection
