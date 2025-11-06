@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Edit Exam Group</h3>
    <a href="{{ route('exams.groups.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  @includeIf('partials.alerts')

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="{{ route('exams.groups.update', $group->id) }}">
        @csrf @method('PUT')

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Name</label>
            <input name="name" class="form-control" required value="{{ old('name',$group->name) }}">
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Exam Type</label>
            <select name="exam_type_id" class="form-select" required>
              @foreach($types as $t)
                <option value="{{ $t->id }}" @selected(old('exam_type_id',$group->exam_type_id)==$t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
              @foreach($years as $y)
                <option value="{{ $y->id }}" @selected(old('academic_year_id',$group->academic_year_id)==$y->id)>{{ $y->year }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-3 mb-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected(old('term_id',$group->term_id)==$t->id)>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description',$group->description) }}</textarea>
          </div>
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" id="groupActive" name="is_active" value="1"
                 @checked(old('is_active', $group->is_active))>
          <label class="form-check-label" for="groupActive">Active</label>
        </div>

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('exams.groups.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
