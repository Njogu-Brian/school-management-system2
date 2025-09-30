@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Add Exam Grade Band</h1>
  <form method="POST" action="{{ route('academics.exam-grades.store') }}" class="mt-3">
    @csrf
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Exam Type*</label>
        <select name="exam_type" class="form-select">
          <option value="OPENER">OPENER</option>
          <option value="MIDTERM">MIDTERM</option>
          <option value="TERM">TERM</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Band*</label>
        <select name="grade_name" class="form-select">
          <option>EE</option><option>ME</option><option>AE</option><option>BE</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Percent From*</label>
        <input type="number" step="0.01" name="percent_from" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Percent Upto*</label>
        <input type="number" step="0.01" name="percent_upto" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Point</label>
        <input type="number" step="0.1" name="grade_point" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">Description</label>
        <input name="description" class="form-control">
      </div>
    </div>
    <button class="btn btn-success mt-3">Save</button>
  </form>
</div>
@endsection
