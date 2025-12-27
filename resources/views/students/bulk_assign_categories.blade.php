@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Bulk Assign Student Categories</h1>
        <p class="text-muted mb-0">Select a class, then set categories for each student.</p>
      </div>
      <a href="{{ route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Students
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><i class="bi bi-1-circle"></i> Step 1: Select Classroom</h5>
      </div>
      <div class="card-body">
        <form method="GET" action="{{ route('students.bulk.assign-categories') }}">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Classroom</label>
              <select name="classroom_id" class="form-select" required onchange="this.form.submit()">
                <option value="">-- Select a Classroom --</option>
                @foreach($classrooms as $classroom)
                  <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                    {{ $classroom->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
        </form>
      </div>
    </div>

    @if($selectedClassroom)
      <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">
            <i class="bi bi-2-circle"></i> Step 2: Assign Categories
            <span class="pill-badge pill-primary ms-2">{{ $selectedClassroom->name }}</span>
          </h5>
        </div>
        <div class="card-body">
          @if($students->count() > 0)
            <form method="POST" action="{{ route('students.bulk.assign-categories.process') }}">
              @csrf
              <input type="hidden" name="classroom_id" value="{{ $selectedClassroom->id }}">
              <div class="table-responsive">
                <table class="table table-modern table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Admission #</th>
                      <th>Name</th>
                      <th>Current Category</th>
                      <th style="width: 240px;">Assign Category</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($students as $student)
                      <tr>
                        <td><span class="pill-badge pill-secondary">{{ $student->admission_number }}</span></td>
                        <td>
                          <div class="fw-semibold">{{ $student->first_name }} {{ $student->last_name }}</div>
                          <small class="text-muted">{{ $student->gender }}</small>
                        </td>
                        <td>
                          @if($student->category)
                            <span class="pill-badge pill-info">{{ $student->category->name }}</span>
                          @else
                            <span class="text-muted">Unassigned</span>
                          @endif
                        </td>
                        <td>
                          <select name="assignments[{{ $student->id }}]" class="form-select" required>
                            @foreach($categories as $category)
                              <option value="{{ $category->id }}" @selected($student->category_id == $category->id)>{{ $category->name }}</option>
                            @endforeach
                          </select>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-settings-primary">
                  <i class="bi bi-save"></i> Save Categories
                </button>
              </div>
            </form>
          @else
            <div class="alert alert-soft border-0">
              <i class="bi bi-info-circle"></i> No active students found in {{ $selectedClassroom->name }}.
            </div>
          @endif
        </div>
      </div>
    @else
      <div class="alert alert-soft border-0">
        <i class="bi bi-info-circle"></i> Please select a classroom to assign categories.
      </div>
    @endif
  </div>
</div>
@endsection

