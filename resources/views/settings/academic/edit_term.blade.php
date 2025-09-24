@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Term</h4>

    <form action="{{ route('settings.academic.term.update', $term) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="name" class="form-label">Term Name</label>
            <input type="text" name="name" id="name" value="{{ $term->name }}" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="academic_year_id" class="form-label">Academic Year</label>
            <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}" {{ $term->academic_year_id == $year->id ? 'selected' : '' }}>
                        {{ $year->year }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_current" id="is_current" value="1" class="form-check-input"{{ old('is_current', $term->is_current ?? false) ? 'checked' : '' }}>
            <label for="is_current" class="form-check-label">Set as Current Term</label>
        </div>

        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('settings.academic.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
