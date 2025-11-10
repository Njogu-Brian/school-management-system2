@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.activities.index', $student) }}">Extracurricular Activities</a></li>
      <li class="breadcrumb-item active">Add Activity</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Add Activity - {{ $student->first_name }} {{ $student->last_name }}</h1>
    <a href="{{ route('students.activities.index', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.activities.store', $student) }}" method="POST">
    @csrf
    <div class="card">
      <div class="card-header">Activity Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Activity Type <span class="text-danger">*</span></label>
            <select name="activity_type" class="form-select" required>
              <option value="">Select Type</option>
              <option value="club" @selected(old('activity_type')=='club')>Club</option>
              <option value="society" @selected(old('activity_type')=='society')>Society</option>
              <option value="sports_team" @selected(old('activity_type')=='sports_team')>Sports Team</option>
              <option value="competition" @selected(old('activity_type')=='competition')>Competition</option>
              <option value="leadership_role" @selected(old('activity_type')=='leadership_role')>Leadership Role</option>
              <option value="community_service" @selected(old('activity_type')=='community_service')>Community Service</option>
              <option value="other" @selected(old('activity_type')=='other')>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Activity Name <span class="text-danger">*</span></label>
            <input type="text" name="activity_name" class="form-control" value="{{ old('activity_name') }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', today()->toDateString()) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Position/Role</label>
            <input type="text" name="position_role" class="form-control" value="{{ old('position_role') }}" placeholder="e.g., Captain, Secretary, Member">
          </div>
          <div class="col-md-6">
            <label class="form-label">Team Name</label>
            <input type="text" name="team_name" class="form-control" value="{{ old('team_name') }}">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Competition Details (if applicable)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Competition Name</label>
            <input type="text" name="competition_name" class="form-control" value="{{ old('competition_name') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Competition Level</label>
            <input type="text" name="competition_level" class="form-control" value="{{ old('competition_level') }}" placeholder="e.g., School, County, National">
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Achievements (if applicable)</h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Award/Achievement</label>
            <input type="text" name="award_achievement" class="form-control" value="{{ old('award_achievement') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Achievement Date</label>
            <input type="date" name="achievement_date" class="form-control" value="{{ old('achievement_date') }}">
          </div>
          <div class="col-md-12">
            <label class="form-label">Achievement Description</label>
            <textarea name="achievement_description" class="form-control" rows="3">{{ old('achievement_description') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Community Service Hours</label>
            <input type="number" name="community_service_hours" class="form-control" value="{{ old('community_service_hours', 0) }}" min="0">
          </div>
        </div>

        <hr class="my-4">

        <div class="row g-3">
          <div class="col-md-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', true))>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.activities.index', $student) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Activity</button>
      </div>
    </div>
  </form>
</div>
@endsection

