@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Stream</h1>
    <form action="{{ route('streams.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Stream Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Select Classrooms</label>
            <div>
                @foreach ($classrooms as $classroom)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="classroom_ids[]" value="{{ $classroom->id }}" id="classroom_{{ $classroom->id }}">
                        <label class="form-check-label" for="classroom_{{ $classroom->id }}">
                            {{ $classroom->name }}
                        </label>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-success">Add Stream</button>
    </form>
</div>
@endsection
