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
            <label>Assign Classroom</label>
            <select name="classroom_id" class="form-control">
                <option value="">Select Classroom</option>
                @foreach ($classrooms as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Add Stream</button>
    </form>
</div>
@endsection
