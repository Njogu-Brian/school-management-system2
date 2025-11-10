@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.activities.index', $student) }}">Extracurricular Activities</a></li>
      <li class="breadcrumb-item active">Edit Activity</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Edit Activity - {{ $student->first_name }} {{ $student->last_name }}</h1>
    <a href="{{ route('students.activities.show', [$student, $activity]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.activities.update', [$student, $activity]) }}" method="POST">
    @csrf @method('PUT')
    <div class="card">
      <div class="card-header">Activity Information</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Activity Type <span class="text-danger">*</span></label>
            <select name="activity_type" class="form-select" required>
              <option value="club" @selected(old('activity_type', $activity->activity_type)=='club')>Club</option>
              <option value="society" @selected(old('activity_type', $activity->activity_type)=='society')>Society</option>
              <option value="sports_team" @selected(old('activity_type', $activity->activity_type)=='sports_team')>Sports Team</option>
              <option value="competition" @selected(old('activity_type', $activity->activity_type)=='competition')>Competition</option>
              <option value="leadership_role" @selected(old('activity_type', $activity->activity_type)=='leadership_role')>Leadership Role</option>
              <option value="community_service" @selected(old('activity_type', $activity->activity_type)=='community_service')>Community Service</option>
              <option value="other" @selected(old('activity_type', $activity->activity_type)=='other')>Other</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Activity Name <span class="text-danger">*</span></label>
            <input type="text" name="activity_name" class="form-control" value="{{ old('activity_name', $activity->activity_name) }}" required>
          </div>
          <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $activity->description) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $activity->start_date->toDateString()) }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $activity->end_date?->toDateString()) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Position/Role</label>
            <input type="text" name="position_role" class="form-control" value="{{ old('position_role', $activity->position_role) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Team Name</label>
            <input type="text" name="team_name" class="form-control" value="{{ old('team_name', $activity->team_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Competition Name</label>
            <input type="text" name="competition_name" class="form-control" value="{{ old('competition_name', $activity->competition_name) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Competition Level</label>
            <input type="text" name="competition_level" class="form-control" value="{{ old('competition_level', $activity->competition_level) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Award/Achievement</label>
            <input type="text" name="award_achievement" class="form-control" value="{{ old('award_achievement', $activity->award_achievement) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Achievement Date</label>
            <input type="date" name="achievement_date" class="form-control" value="{{ old('achievement_date', $activity->achievement_date?->toDateString()) }}">
          </div>
          <div class="col-md-12">
            <label class="form-label">Achievement Description</label>
            <textarea name="achievement_description" class="form-control" rows="3">{{ old('achievement_description', $activity->achievement_description) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Community Service Hours</label>
            <input type="number" name="community_service_hours" class="form-control" value="{{ old('community_service_hours', $activity->community_service_hours) }}" min="0">
          </div>
          <div class="col-md-6">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $activity->is_active))>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
        </div>

        <hr class="my-4">

        <h6 class="text-muted mb-3">Billing Information</h6>
        <div class="alert alert-info small mb-3">
          <i class="bi bi-info-circle"></i> Link this activity to optional fees for automatic billing.
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Fee Category (Votehead)</label>
            <select name="votehead_id" class="form-select">
              <option value="">No Fee Category</option>
              @foreach($voteheads as $votehead)
                <option value="{{ $votehead->id }}" @selected(old('votehead_id', $activity->votehead_id)==$votehead->id)>{{ $votehead->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fee Amount</label>
            <input type="number" name="fee_amount" class="form-control" value="{{ old('fee_amount', $activity->fee_amount) }}" step="0.01" min="0">
            <small class="text-muted">Override amount from fee structure</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">Billing Term</label>
            <select name="billing_term" class="form-select">
              <option value="">Auto</option>
              <option value="1" @selected(old('billing_term', $activity->billing_term)==1)>Term 1</option>
              <option value="2" @selected(old('billing_term', $activity->billing_term)==2)>Term 2</option>
              <option value="3" @selected(old('billing_term', $activity->billing_term)==3)>Term 3</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Billing Year</label>
            <input type="number" name="billing_year" class="form-control" value="{{ old('billing_year', $activity->billing_year ?? $currentYear?->year) }}" min="2000" max="2100">
          </div>
          <div class="col-md-4">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="auto_bill" value="1" id="auto_bill" @checked(old('auto_bill', $activity->auto_bill ?? true))>
              <label class="form-check-label" for="auto_bill">Auto-bill Fee</label>
            </div>
          </div>
          @if($activity->votehead_id && $activity->optionalFee)
          <div class="col-md-12">
            <div class="alert alert-success small">
              <i class="bi bi-check-circle"></i> Fee has been billed. View in <a href="{{ route('finance.optional-fees.student-view', ['student_id' => $student->id, 'term' => $activity->billing_term, 'year' => $activity->billing_year]) }}" target="_blank">Optional Fees</a>
            </div>
          </div>
          @endif
          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $activity->notes) }}</textarea>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('students.activities.show', [$student, $activity]) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Activity</button>
      </div>
    </div>
  </form>
</div>
@endsection

