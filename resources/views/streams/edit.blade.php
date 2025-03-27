@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Stream</h1>

    <form action="{{ route('streams.update', $stream->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Stream Name</label>
            <input type="text" name="name" class="form-control" value="{{ $stream->name }}" required>
        </div>

        <div class="mb-3">
            <label>Assign Classroom</label>
            <select name="classroom_id" class="form-control">
                <option value="">Select Classroom</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}" {{ $stream->classroom_id == $classroom->id ? 'selected' : '' }}>
                        {{ $classroom->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Stream</button>
    </form>
</div>
@endsection
