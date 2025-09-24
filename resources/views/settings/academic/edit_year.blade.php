@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Academic Year</h4>

    <form action="{{ route('settings.academic.year.update', $year) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="year" class="form-label">Year</label>
            <input type="text" name="year" id="year" value="{{ $year->year }}" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input"{{ old('is_active', $year->is_active ?? false) ? 'checked' : '' }}>
            <label for="is_active" class="form-check-label">Set as Active Year</label>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('settings.academic.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
