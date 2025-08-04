@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Add Academic Year</h4>
    <form action="{{ route('academic-years.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Year</label>
            <input type="text" name="year" class="form-control" required placeholder="e.g. 2025">
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" class="form-check-input">
            <label class="form-check-label">Set as Active</label>
        </div>
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
