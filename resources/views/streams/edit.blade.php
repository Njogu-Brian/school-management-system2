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
            <label>Assign Classrooms</label>
            <div class="row">
                @foreach ($classrooms as $classroom)
                    <div class="col-md-4">
                        <input type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}"
                        {{ $stream->classrooms->contains($classroom->id) ? 'checked' : '' }}>
                        <label>{{ $classroom->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Update Stream</button>
    </form>
</div>
@endsection
