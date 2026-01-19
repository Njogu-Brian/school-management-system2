@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Update Import' => route('students.update-import'), 'Preview' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Preview Updates</h1>
        <p class="text-muted mb-0">Review the changes before importing.</p>
      </div>
      <a href="{{ route('students.update-import') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @if(!empty($errors))
      <div class="alert alert-danger">
        <h6><i class="bi bi-exclamation-triangle me-2"></i>Errors Found:</h6>
        <ul class="mb-0">
          @foreach($errors as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if($successCount > 0)
      <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i>
        Found <strong>{{ $successCount }}</strong> valid student(s) to update.
      </div>

      <form action="{{ route('students.update-import.process') }}" method="POST">
        @csrf
        
        <div class="settings-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Students to Update</span>
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-check-circle me-2"></i>Confirm & Import Updates
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Admission #</th>
                    <th>Student Name</th>
                    <th>Current Class</th>
                    <th>Updates</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($preview as $item)
                    <tr>
                      <td>
                        <strong>{{ $item['admission_number'] }}</strong>
                        <input type="hidden" name="students[]" value="{{ base64_encode(json_encode($item['row_data'])) }}">
                      </td>
                      <td>{{ $item['student_name'] }}</td>
                      <td>{{ $item['current_classroom'] ?: '—' }}</td>
                      <td>
                        <small class="text-muted">
                          @php
                            $updates = [];
                            $rowData = $item['row_data'];
                            $student = $item['student'];
                            
                            // Check what's being updated
                            if (!empty($rowData['first_name']) && $rowData['first_name'] != $student->first_name) $updates[] = 'First Name';
                            if (!empty($rowData['last_name']) && $rowData['last_name'] != $student->last_name) $updates[] = 'Last Name';
                            if (!empty($rowData['classroom']) && $student->classroom && $rowData['classroom'] != $student->classroom->name) $updates[] = 'Classroom';
                            if (!empty($rowData['father_name']) && $student->parent && $rowData['father_name'] != $student->parent->father_name) $updates[] = 'Father Name';
                            if (!empty($rowData['mother_name']) && $student->parent && $rowData['mother_name'] != $student->parent->mother_name) $updates[] = 'Mother Name';
                            if (!empty($rowData['father_phone'])) $updates[] = 'Father Phone';
                            if (!empty($rowData['mother_phone'])) $updates[] = 'Mother Phone';
                            if (!empty($rowData['residential_area'])) $updates[] = 'Residential Area';
                            if (!empty($rowData['emergency_contact_name'])) $updates[] = 'Emergency Contact';
                          @endphp
                          @if(count($updates) > 0)
                            {{ implode(', ', array_slice($updates, 0, 5)) }}{{ count($updates) > 5 ? '...' : '' }}
                          @else
                            <span class="text-muted">No changes detected</span>
                          @endif
                        </small>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </form>
    @else
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No valid students found to update. Please check your file and try again.
      </div>
    @endif
  </div>
</div>
@endsection
