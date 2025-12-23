@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Events / Edit</div>
                <h1>Edit Event</h1>
                <p>Update details without losing visibility and schedule context.</p>
                <div class="d-flex gap-2 mt-2">
                    <span class="settings-chip"><i class="bi bi-pencil-square"></i> Editing</span>
                    <span class="settings-chip"><i class="bi bi-calendar-week"></i> {{ $event->start_date->format('M j, Y') }}</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('events.show', $event) }}" class="btn btn-ghost-strong">
                    <i class="bi bi-arrow-left"></i> Back to Event
                </a>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Event Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('events.update', $event) }}" method="POST" class="row g-3">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $event->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="academic" {{ old('type', $event->type) == 'academic' ? 'selected' : '' }}>Academic</option>
                            <option value="sports" {{ old('type', $event->type) == 'sports' ? 'selected' : '' }}>Sports</option>
                            <option value="cultural" {{ old('type', $event->type) == 'cultural' ? 'selected' : '' }}>Cultural</option>
                            <option value="holiday" {{ old('type', $event->type) == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            <option value="meeting" {{ old('type', $event->type) == 'meeting' ? 'selected' : '' }}>Meeting</option>
                            <option value="other" {{ old('type', $event->type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $event->description) }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                               value="{{ old('start_date', $event->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
                               value="{{ old('end_date', $event->end_date ? $event->end_date->format('Y-m-d') : '') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-3">
                            <input type="checkbox" name="is_all_day" class="form-check-input" id="is_all_day"
                                   {{ old('is_all_day', $event->is_all_day) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_all_day">All Day Event</label>
                        </div>
                    </div>

                    <div class="col-12" id="time-fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Time</label>
                                <input type="time" name="start_time" class="form-control"
                                       value="{{ old('start_time', $event->start_time ? date('H:i', strtotime($event->start_time)) : '') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Time</label>
                                <input type="time" name="end_time" class="form-control"
                                       value="{{ old('end_time', $event->end_time ? date('H:i', strtotime($event->end_time)) : '') }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Venue</label>
                        <input type="text" name="venue" class="form-control" value="{{ old('venue', $event->venue) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Visibility <span class="text-danger">*</span></label>
                        <select name="visibility" class="form-select" required>
                            <option value="public" {{ old('visibility', $event->visibility) == 'public' ? 'selected' : '' }}>Public</option>
                            <option value="staff" {{ old('visibility', $event->visibility) == 'staff' ? 'selected' : '' }}>Staff Only</option>
                            <option value="students" {{ old('visibility', $event->visibility) == 'students' ? 'selected' : '' }}>Students Only</option>
                            <option value="parents" {{ old('visibility', $event->visibility) == 'parents' ? 'selected' : '' }}>Parents Only</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Academic Year (Optional)</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">Select Year</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id', $event->academic_year_id) == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-ghost">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-settings-primary px-4">
                            <i class="bi bi-save"></i> Update Event
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