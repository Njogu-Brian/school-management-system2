@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Academic Calendar</div>
                <h1>Edit Term</h1>
                <p>Update dates, status, and expectations for this term.</p>
            </div>
            <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost"><i class="bi bi-arrow-left"></i> Back to calendar</a>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Term Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.academic.term.update', $term) }}" method="POST" class="row g-4">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label for="name" class="form-label fw-semibold">Term Name</label>
                        <input type="text" name="name" id="name" value="{{ $term->name }}" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label for="academic_year_id" class="form-label fw-semibold">Academic Year</label>
                        <select name="academic_year_id" id="academic_year_id" class="form-select" required>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ $term->academic_year_id == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_current" id="is_current" value="1" class="form-check-input"{{ old('is_current', $term->is_current ?? false) ? 'checked' : '' }}>
                            <label for="is_current" class="form-check-label">Set as Current Term</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="opening_date" class="form-label fw-semibold">Opening Date <span class="text-danger">*</span></label>
                        <input type="date" name="opening_date" id="opening_date" class="form-control" value="{{ old('opening_date', $term->opening_date?->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="closing_date" class="form-label fw-semibold">Closing Date <span class="text-danger">*</span></label>
                        <input type="date" name="closing_date" id="closing_date" class="form-control" value="{{ old('closing_date', $term->closing_date?->toDateString()) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label for="midterm_start_date" class="form-label">Midterm Start Date</label>
                        <input type="date" name="midterm_start_date" id="midterm_start_date" class="form-control" value="{{ old('midterm_start_date', $term->midterm_start_date?->toDateString()) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="midterm_end_date" class="form-label">Midterm End Date</label>
                        <input type="date" name="midterm_end_date" id="midterm_end_date" class="form-control" value="{{ old('midterm_end_date', $term->midterm_end_date?->toDateString()) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="expected_school_days" class="form-label">Expected School Days</label>
                        <input type="number" name="expected_school_days" id="expected_school_days" class="form-control" value="{{ old('expected_school_days', $term->expected_school_days) }}" min="0" placeholder="Auto-calculated if empty">
                        @if($term->opening_date && $term->closing_date)
                            <div class="form-note mt-1">Actual: {{ $term->calculateActualSchoolDays() }} days</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $term->notes) }}</textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary px-4">Update Term</button>
                        <a href="{{ route('settings.academic.index') }}" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
