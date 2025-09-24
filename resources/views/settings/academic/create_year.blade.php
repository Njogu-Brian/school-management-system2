@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Add Academic Year</h4>

    <form action="{{ route('settings.academic.year.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="year" class="form-label">Year</label>
            <input type="text" name="year" id="year" class="form-control" placeholder="e.g. 2025" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input">
            <label for="is_active" class="form-check-label">Set as Active Year</label>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('settings.academic.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
