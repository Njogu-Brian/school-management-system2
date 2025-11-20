@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Edit Stream</h2>
            <small class="text-muted">Update stream information and classroom assignments</small>
        </div>
        <a href="{{ route('academics.streams.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-pencil"></i> Stream Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('academics.streams.update', $stream->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Stream Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $stream->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Primary Classroom <span class="text-danger">*</span></label>
                    <select name="classroom_id" class="form-select" required>
                        <option value="">-- Select Primary Classroom --</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(old('classroom_id', $stream->classroom_id) == $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select the primary classroom this stream belongs to</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign to Additional Classrooms</label>
                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                        <div class="row">
                            @foreach ($classrooms as $classroom)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="classroom_ids[]" 
                                            value="{{ $classroom->id }}" id="classroom_{{ $classroom->id }}"
                                            {{ (old('classroom_ids') && in_array($classroom->id, old('classroom_ids'))) || (isset($assignedClassrooms) && in_array($classroom->id, $assignedClassrooms)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="classroom_{{ $classroom->id }}">
                                            {{ $classroom->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <small class="text-muted">Select additional classrooms this stream should be available in (optional)</small>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('academics.streams.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Update Stream
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
