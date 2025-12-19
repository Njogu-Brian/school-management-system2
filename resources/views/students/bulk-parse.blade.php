@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Bulk Upload' => route('students.bulk'), 'Preview' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Preview Import</h1>
        <p class="text-muted mb-0">Review parsed rows before importing students.</p>
      </div>
      <a href="{{ route('students.bulk') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <form action="{{ route('students.bulk.import') }}" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="alert alert-soft border-0">
          <i class="bi bi-info-circle me-1"></i> Review the rows. Missing or duplicate admission numbers can be auto-assigned during import.
        </div>

        <div class="table-responsive">
          <table class="table table-modern align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Admission</th>
                <th>First</th>
                <th>Last</th>
                <th>Gender</th>
                <th>DOB</th>
                <th>Classroom</th>
                <th>Parent Phone</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($students as $index => $row)
                @php $valid = $row['valid']; @endphp
                <tr class="{{ $valid ? '' : 'table-danger' }}">
                  <td>{{ $index + 1 }}</td>
                  <td>{{ $row['admission_number'] ?? 'Auto' }}</td>
                  <td>{{ $row['first_name'] }}</td>
                  <td>{{ $row['last_name'] }}</td>
                  <td>{{ $row['gender'] }}</td>
                  <td>{{ $row['dob'] }}</td>
                  <td>{{ $row['classroom_name'] }}</td>
                  <td>{{ $row['father_phone'] ?? '-' }}</td>
                  <td>
                    <span class="pill-badge pill-{{ $valid ? 'success' : 'danger' }}">
                      {{ $valid ? 'Ready' : 'Invalid' }}
                    </span>
                  </td>
                  <input type="hidden" name="students[]" value="{{ base64_encode(json_encode($row)) }}">
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-settings-primary" {{ !$allValid ? 'disabled' : '' }}>
          <i class="bi bi-cloud-upload"></i> Confirm & Import
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
