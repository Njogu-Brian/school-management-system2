@extends('layouts.app')

@section('content')
<div class="container">
  @include('students.partials.breadcrumbs', ['trail' => ['Bulk Upload' => route('students.bulk'), 'Preview' => null]])

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Preview Import</h1>
    <a href="{{ route('students.bulk') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.bulk.import') }}" method="POST" class="card">
    @csrf
    <div class="card-body">
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i> Review the rows. Missing or duplicate admission numbers can be auto-assigned during import.
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-dark">
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
                  @if ($valid)
                    <span class="badge bg-success">Ready</span>
                  @else
                    <span class="badge bg-danger">Invalid</span>
                  @endif
                </td>
                <input type="hidden" name="students[]" value="{{ base64_encode(json_encode($row)) }}">
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      <button type="submit" class="btn btn-success" {{ !$allValid ? 'disabled' : '' }}>
        <i class="bi bi-cloud-upload"></i> Confirm & Import
      </button>
    </div>
  </form>
</div>
@endsection
