@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Add Term</h4>

    <form action="{{ route('settings.academic.term.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">Term Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Term 1" required>
        </div>

        <div class="mb-3">
            <label for="academic_year_id" class="form-label">Academic Year</label>
            <select name="academic_year_id" id="academic_year_id" class="form-control" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->year }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="is_current" id="is_current" value="1" class="form-check-input"
       {{ old('is_current', $term->is_current ?? false) ? 'checked' : '' }}>
            <label for="is_current" class="form-check-label">Set as Current Term</label>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="opening_date" class="form-label">Opening Date <span class="text-danger">*</span></label>
                <input type="date" name="opening_date" id="opening_date" class="form-control" value="{{ old('opening_date') }}" required>
            </div>
            <div class="col-md-6">
                <label for="closing_date" class="form-label">Closing Date <span class="text-danger">*</span></label>
                <input type="date" name="closing_date" id="closing_date" class="form-control" value="{{ old('closing_date') }}" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="midterm_start_date" class="form-label">Midterm Start Date</label>
                <input type="date" name="midterm_start_date" id="midterm_start_date" class="form-control" value="{{ old('midterm_start_date') }}">
            </div>
            <div class="col-md-6">
                <label for="midterm_end_date" class="form-label">Midterm End Date</label>
                <input type="date" name="midterm_end_date" id="midterm_end_date" class="form-control" value="{{ old('midterm_end_date') }}">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="expected_school_days" class="form-label">Expected School Days</label>
                <input type="number" name="expected_school_days" id="expected_school_days" class="form-control" value="{{ old('expected_school_days') }}" min="0" placeholder="Auto-calculated if empty">
            </div>
        </div>

        <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>

        <button type="submit" class="btn btn-success">Save</button>
        <a href="{{ route('settings.academic.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
