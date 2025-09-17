@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Send SMS</h4>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form action="{{ route('communication.send.sms.submit') }}" method="POST">
        @csrf

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Target *</label>
                <select name="target" class="form-control" required>
                    <option value="parents">Parents</option>
                    <option value="students">Students</option>
                    <option value="teachers">Teachers</option>
                    <option value="staff">All Staff</option>
                    <option value="custom">Custom</option>
                </select>
            </div>

            <div class="col-md-8">
                <label class="form-label">Custom Numbers (comma separated)</label>
                <input type="text" name="custom_numbers" class="form-control" placeholder="+2547xxxxxxxx, +2547yyyyyyyy">
            </div>

            <div class="col-md-6">
                <label class="form-label">Use Template (optional)</label>
                <select name="template_code" class="form-control">
                    <option value="">— None —</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->code }}">{{ $t->title }} ({{ $t->code }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Selecting a template will prefill message. You can still override below.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">Message (override)</label>
                <textarea name="message" rows="6" maxlength="300" class="form-control" placeholder="Leave blank to use template content"></textarea>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Send</button>
        </div>
    </form>
</div>
@endsection
