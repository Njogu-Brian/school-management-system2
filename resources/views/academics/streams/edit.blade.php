@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Edit Stream</h2>
            <small class="text-muted">Update stream information (each stream belongs to one classroom)</small>
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
                    <label class="form-label">Classroom <span class="text-danger">*</span></label>
                    <select name="classroom_id" class="form-select" required>
                        <option value="">-- Select Classroom --</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(old('classroom_id', $stream->classroom_id) == $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select the classroom this stream belongs to. Note: Stream names can be the same across different classrooms (e.g., "A" stream in Grade 1 and "A" stream in Grade 2 are different streams).</small>
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
