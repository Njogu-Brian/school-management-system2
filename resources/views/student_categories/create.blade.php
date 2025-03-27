@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add New Student Category</h1>

    <form action="{{ route('student-categories.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Category Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Add Category</button>
    </form>
</div>
@endsection
