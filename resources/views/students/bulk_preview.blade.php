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
        <h1 class="mb-1">Bulk Upload Preview</h1>
        <p class="text-muted mb-0">Verify the parsed rows before importing.</p>
      </div>
      <a href="{{ route('students.bulk') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <form action="{{ route('students.bulk.import') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="alert alert-soft border-0">
          <i class="bi bi-info-circle"></i> Students with missing or duplicate admission numbers will be auto-assigned during import.
        </div>

        <div class="table-responsive">
          <table class="table table-modern align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Admission Number</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Classroom</th>
                <th>Parent Phone</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($students as $index => $student)
                <tr class="{{ $student['valid'] ? '' : 'table-danger' }}">
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $student['admission_number'] ?? 'Auto' }}</td>
                  <td>{{ $student['first_name'] }}</td>
                  <td>{{ $student['last_name'] }}</td>
                  <td>{{ $student['gender'] }}</td>
                  <td>{{ $student['dob'] }}</td>
                  <td>{{ $student['classroom_name'] }}</td>
                  <td>{{ $student['father_phone'] ?? '-' }}</td>
                  <td>
                    <span class="pill-badge pill-{{ $student['valid'] ? 'success' : 'danger' }}">{{ $student['valid'] ? 'Ready' : 'Invalid' }}</span>
                  </td>
                  <input type="hidden" name="students[]" value="{{ base64_encode(json_encode($student)) }}">
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-settings-primary" {{ !$allValid ? 'disabled' : '' }}>
          <i class="bi bi-upload"></i> Confirm & Import
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
