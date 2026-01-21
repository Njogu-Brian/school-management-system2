@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item active">Medical Records</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Medical Records - {{ $student->full_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Student</a>
      <a href="{{ route('students.medical-records.create', $student) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Record</a>
    </div>
  </div>

  @include('students.partials.alerts')

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Title</th>
            <th>Doctor/Clinic</th>
            <th>Next Due</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $record)
            <tr>
              <td>{{ $record->record_date->format('M d, Y') }}</td>
              <td><span class="badge bg-info">{{ ucfirst($record->record_type) }}</span></td>
              <td class="fw-semibold">{{ $record->title }}</td>
              <td>
                @if($record->doctor_name)
                  {{ $record->doctor_name }}
                  @if($record->clinic_hospital) <br><small class="text-muted">{{ $record->clinic_hospital }}</small> @endif
                @else
                  —
                @endif
              </td>
              <td>{{ $record->next_due_date ? $record->next_due_date->format('M d, Y') : '—' }}</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('students.medical-records.show', [$student, $record]) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                  <a href="{{ route('students.medical-records.edit', [$student, $record]) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                  <form action="{{ route('students.medical-records.destroy', [$student, $record]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this medical record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No medical records found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">
      {{ $records->links() }}
    </div>
    @endif
  </div>
</div>
@endsection

