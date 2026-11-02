@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Online Admissions</div>
        <h1 class="mb-1">Fill admission form</h1>
        <p class="text-muted mb-0">Complete fields on phone or laptop, then print for parent signature.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('online-admissions.show', $admission) }}" class="btn btn-ghost-strong">Back</a>
        <a href="{{ route('online-admissions.admission-form.print', $admission) }}" class="btn btn-settings-primary">Print PDF</a>
      </div>
    </div>

    <form action="{{ route('online-admissions.admission-form.update', $admission) }}" method="POST" class="settings-card">
      @csrf
      @method('PUT')
      <div class="card-body">
        <h5 class="mb-3">Learner</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-4"><label class="form-label">First name</label><input name="first_name" class="form-control" value="{{ old('first_name', $admission->first_name) }}" required></div>
          <div class="col-md-4"><label class="form-label">Middle name</label><input name="middle_name" class="form-control" value="{{ old('middle_name', $admission->middle_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Last name</label><input name="last_name" class="form-control" value="{{ old('last_name', $admission->last_name) }}" required></div>
          <div class="col-md-4"><label class="form-label">DOB</label><input type="date" name="dob" class="form-control" value="{{ old('dob', optional($admission->dob)->toDateString()) }}"></div>
          <div class="col-md-4"><label class="form-label">Gender</label>
            <select name="gender" class="form-select">
              <option value="">—</option>
              <option value="Male" @selected(old('gender', $admission->gender)=='Male')>Male</option>
              <option value="Female" @selected(old('gender', $admission->gender)=='Female')>Female</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Preferred class</label>
            <select name="preferred_classroom_id" class="form-select">
              <option value="">—</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" @selected(old('preferred_classroom_id', $admission->preferred_classroom_id)==$classroom->id)>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Previous school</label><input name="previous_school" class="form-control" value="{{ old('previous_school', $admission->previous_school) }}"></div>
          <div class="col-md-6"><label class="form-label">Residential area</label><input name="residential_area" class="form-control" value="{{ old('residential_area', $admission->residential_area) }}"></div>
          <div class="col-md-6"><label class="form-label">NEMIS / KEMIS</label><input name="nemis_number" class="form-control" value="{{ old('nemis_number', $admission->nemis_number) }}"></div>
        </div>

        <h5 class="mb-3">Parents</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-4"><label class="form-label">Father name</label><input name="father_name" class="form-control" value="{{ old('father_name', $admission->father_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Father phone</label><input name="father_phone" class="form-control" value="{{ old('father_phone', $admission->father_phone) }}"></div>
          <div class="col-md-4"><label class="form-label">Father email</label><input name="father_email" class="form-control" value="{{ old('father_email', $admission->father_email) }}"></div>
          <div class="col-md-4"><label class="form-label">Father ID</label><input name="father_id_number" class="form-control" value="{{ old('father_id_number', $admission->father_id_number) }}"></div>
          <div class="col-md-4"><label class="form-label">Mother name</label><input name="mother_name" class="form-control" value="{{ old('mother_name', $admission->mother_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Mother phone</label><input name="mother_phone" class="form-control" value="{{ old('mother_phone', $admission->mother_phone) }}"></div>
          <div class="col-md-4"><label class="form-label">Mother email</label><input name="mother_email" class="form-control" value="{{ old('mother_email', $admission->mother_email) }}"></div>
          <div class="col-md-4"><label class="form-label">Mother ID</label><input name="mother_id_number" class="form-control" value="{{ old('mother_id_number', $admission->mother_id_number) }}"></div>
          <div class="col-md-4"><label class="form-label">Guardian name</label><input name="guardian_name" class="form-control" value="{{ old('guardian_name', $admission->guardian_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Guardian phone</label><input name="guardian_phone" class="form-control" value="{{ old('guardian_phone', $admission->guardian_phone) }}"></div>
          <div class="col-md-4"><label class="form-label">Marital status</label>
            <select name="marital_status" class="form-select">
              <option value="">—</option>
              <option value="married" @selected(old('marital_status', $admission->marital_status)=='married')>Married</option>
              <option value="single_parent" @selected(old('marital_status', $admission->marital_status)=='single_parent')>Single parent</option>
              <option value="co_parenting" @selected(old('marital_status', $admission->marital_status)=='co_parenting')>Co-parenting</option>
            </select>
          </div>
        </div>

        <h5 class="mb-3">Medical & emergency</h5>
        <div class="row g-3 mb-4">
          <div class="col-md-4"><label class="form-label">Has allergies?</label>
            <select name="has_allergies" class="form-select">
              <option value="0" @selected(!old('has_allergies', $admission->has_allergies))>No</option>
              <option value="1" @selected(old('has_allergies', $admission->has_allergies))>Yes</option>
            </select>
          </div>
          <div class="col-md-8"><label class="form-label">Allergy notes</label><input name="allergies_notes" class="form-control" value="{{ old('allergies_notes', $admission->allergies_notes) }}"></div>
          <div class="col-md-4"><label class="form-label">Fully immunized?</label>
            <select name="is_fully_immunized" class="form-select">
              <option value="0" @selected(!old('is_fully_immunized', $admission->is_fully_immunized))>No</option>
              <option value="1" @selected(old('is_fully_immunized', $admission->is_fully_immunized))>Yes</option>
            </select>
          </div>
          <div class="col-md-4"><label class="form-label">Preferred hospital</label><input name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital', $admission->preferred_hospital) }}"></div>
          <div class="col-md-4"><label class="form-label">Emergency contact</label><input name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $admission->emergency_contact_name) }}"></div>
          <div class="col-md-4"><label class="form-label">Emergency phone</label><input name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone', $admission->emergency_contact_phone) }}"></div>
          <div class="col-md-8"><label class="form-label">Medical condition</label><textarea name="medical_condition" class="form-control" rows="2">{{ old('medical_condition', $admission->medical_condition) }}</textarea></div>
        </div>

        <button type="submit" class="btn btn-settings-primary"><i class="bi bi-save"></i> Save form</button>
      </div>
    </form>
  </div>
</div>
@endsection
