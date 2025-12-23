@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Events / Create</div>
                <h1>Create Event</h1>
                <p>Plan key dates, set visibility, and keep everyone aligned.</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="settings-chip"><i class="bi bi-calendar-week"></i> Calendar</span>
                    <span class="settings-chip"><i class="bi bi-people"></i> School community</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('events.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Calendar
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Event Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('events.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="academic" {{ old('type') == 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="sports" {{ old('type') == 'sports' ? 'selected' : '' }}>Sports</option>
                            <option value="cultural" {{ old('type') == 'cultural' ? 'selected' : '' }}>Cultural</option>
                            <option value="holiday" {{ old('type') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            <option value="meeting" {{ old('type') == 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                            <input type="checkbox" name="is_all_day" class="form-check-input" id="is_all_day"
                                   {{ old('is_all_day') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_all_day">All Day Event</label>
                        </div>
                    </div>

                    <div class="col-12" id="time-fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Venue</label>
                        <input type="text" name="venue" class="form-control" value="{{ old('venue') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Visibility <span class="text-danger">*</span></label>
                        <select name="visibility" class="form-select" required>
                            <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>Public</option>
                            <option value="staff" {{ old('visibility') == 'staff' ? 'selected' : '' }}>Staff Only</option>
                            <option value="students" {{ old('visibility') == 'students' ? 'selected' : '' }}>Students Only</option>
                            <option value="parents" {{ old('visibility') == 'parents' ? 'selected' : '' }}>Parents Only</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Academic Year (Optional)</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">Select Year</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('events.index') }}" class="btn btn-ghost">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-settings-primary px-4">
                            <i class="bi bi-check2-circle"></i> Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('is_all_day').addEventListener('change', function() {
        const timeFields = document.getElementById('time-fields');
        timeFields.style.display = this.checked ? 'none' : 'block';
    });
    
    // Trigger on page load
    document.getElementById('is_all_day').dispatchEvent(new Event('change'));
</script>
@endsection