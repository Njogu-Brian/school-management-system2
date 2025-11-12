@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Extra-Curricular Activity</h1>
        <a href="{{ route('academics.extra-curricular-activities.show', $extra_curricular_activity) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.extra-curricular-activities.update', $extra_curricular_activity) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $extra_curricular_activity->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Select Type</option>
                            <option value="club" {{ old('type', $extra_curricular_activity->type) == 'club' ? 'selected' : '' }}>Club</option>
                            <option value="sport" {{ old('type', $extra_curricular_activity->type) == 'sport' ? 'selected' : '' }}>Sport</option>
                            <option value="event" {{ old('type', $extra_curricular_activity->type) == 'event' ? 'selected' : '' }}>Event</option>
                            <option value="parade" {{ old('type', $extra_curricular_activity->type) == 'parade' ? 'selected' : '' }}>Parade</option>
                            <option value="other" {{ old('type', $extra_curricular_activity->type) == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Day</label>
                        <select name="day" class="form-select">
                            <option value="">Select Day</option>
                            <option value="Monday" {{ old('day', $extra_curricular_activity->day) == 'Monday' ? 'selected' : '' }}>Monday</option>
                            <option value="Tuesday" {{ old('day', $extra_curricular_activity->day) == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                            <option value="Wednesday" {{ old('day', $extra_curricular_activity->day) == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                            <option value="Thursday" {{ old('day', $extra_curricular_activity->day) == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                            <option value="Friday" {{ old('day', $extra_curricular_activity->day) == 'Friday' ? 'selected' : '' }}>Friday</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" 
                               value="{{ old('start_time', $extra_curricular_activity->start_time ? $extra_curricular_activity->start_time->format('H:i') : '') }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" 
                               value="{{ old('end_time', $extra_curricular_activity->end_time ? $extra_curricular_activity->end_time->format('H:i') : '') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Period</label>
                        <input type="number" name="period" class="form-control" min="1" max="10" 
                               value="{{ old('period', $extra_curricular_activity->period) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror" required>
                            <option value="">Select Year</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}" {{ old('academic_year_id', $extra_curricular_activity->academic_year_id) == $year->id ? 'selected' : '' }}>
                                    {{ $year->year }}
                                </option>
                            @endforeach
                        </select>
                        @error('academic_year_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Term <span class="text-danger">*</span></label>
                        <select name="term_id" class="form-select @error('term_id') is-invalid @enderror" required>
                            <option value="">Select Term</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ old('term_id', $extra_curricular_activity->term_id) == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('term_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Classrooms</label>
                    <select name="classroom_ids[]" class="form-select" multiple>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" 
                                {{ in_array($classroom->id, old('classroom_ids', $extra_curricular_activity->classroom_ids ?? [])) ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Supervising Staff</label>
                    <select name="staff_ids[]" class="form-select" multiple>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}" 
                                {{ in_array($member->id, old('staff_ids', $extra_curricular_activity->staff_ids ?? [])) ? 'selected' : '' }}>
                                {{ $member->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $extra_curricular_activity->description) }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" 
                                   {{ old('is_active', $extra_curricular_activity->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="repeat_weekly" value="1" id="repeat_weekly" 
                                   {{ old('repeat_weekly', $extra_curricular_activity->repeat_weekly) ? 'checked' : '' }}>
                            <label class="form-check-label" for="repeat_weekly">
                                Repeat Weekly
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.extra-curricular-activities.show', $extra_curricular_activity) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Activity</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

