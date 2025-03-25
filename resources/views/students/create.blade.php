@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Student</h1>

    <form action="{{ route('students.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Admission Number</label>
            <input type="text" name="admission_number" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Class</label>
            <input type="text" name="class" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Parent</label>
            <select name="parent_id" class="form-control">
                <option value="">No Parent</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-success">Save Student</button>
    </form>
</div>
@endsection
