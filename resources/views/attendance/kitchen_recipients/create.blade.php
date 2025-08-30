@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Add Kitchen Recipient</h4>

    <form action="{{ route('attendance.kitchen.recipients.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Label *</label>
            <input type="text" name="label" class="form-control" required placeholder="Chef, Janitor, etc.">
        </div>

        <div class="mb-3">
            <label class="form-label">Select Staff *</label>
            <select name="staff_id" class="form-select" required>
                <option value="">-- Select Staff --</option>
                @foreach($staff as $s)
                    <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }} ({{ $s->phone_number }})</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Assign Classes</label>
            <select name="classroom_ids[]" class="form-select" multiple>
                @foreach($classrooms as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <small class="text-muted">Leave empty to assign ALL classes</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Active?</label>
            <select name="active" class="form-select">
                <option value="1" selected>Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Save Recipient</button>
        <a href="{{ route('attendance.kitchen.recipients.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
