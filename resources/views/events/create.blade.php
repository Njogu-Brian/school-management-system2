@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Event</h1>
        <a href="{{ route('events.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('events.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                               value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
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
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Date (Optional)</label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                               value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_all_day" class="form-check-input" id="is_all_day" 
                                   {{ old('is_all_day') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_all_day">All Day Event</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3" id="time-fields">
                    <div class="col-md-6">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" class="form-control" value="{{ old('venue') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Visibility <span class="text-danger">*</span></label>
                        <select name="visibility" class="form-select" required>
                            <option value="public" {{ old('visibility') == 'public' ? 'selected' : '' }}>Public</option>
                            <option value="staff" {{ old('visibility') == 'staff' ? 'selected' : '' }}>Staff Only</option>
                            <option value="students" {{ old('visibility') == 'students' ? 'selected' : '' }}>Students Only</option>
                            <option value="parents" {{ old('visibility') == 'parents' ? 'selected' : '' }}>Parents Only</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Academic Year (Optional)</label>
                    <select name="academic_year_id" class="form-select">
                        <option value="">Select Year</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('events.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
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

