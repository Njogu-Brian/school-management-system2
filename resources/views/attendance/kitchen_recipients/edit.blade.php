@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Edit Kitchen Recipient</h4>

    <form action="{{ route('attendance.kitchen.recipients.update', $recipient->id) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label class="form-label">Label *</label>
            <input type="text" name="label" class="form-control" value="{{ $recipient->label }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Select Staff *</label>
            <select name="staff_id" class="form-select" required>
                @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $recipient->staff_id == $s->id ? 'selected' : '' }}>
                        {{ $s->first_name }} {{ $s->last_name }} ({{ $s->phone_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Assign Classes</label>
            <select name="classroom_ids[]" class="form-select" multiple>
                @foreach($classrooms as $id => $name)
                    <option value="{{ $id }}" {{ in_array($id, $recipient->classroom_ids ?? []) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Leave empty to assign ALL classes</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Active?</label>
            <select name="active" class="form-select">
                <option value="1" {{ $recipient->active ? 'selected' : '' }}>Yes</option>
                <option value="0" {{ !$recipient->active ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Update Recipient</button>
        <a href="{{ route('attendance.kitchen.recipients.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
