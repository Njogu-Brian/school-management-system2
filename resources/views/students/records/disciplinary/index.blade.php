@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->first_name }} {{ $student->last_name }}</a></li>
      <li class="breadcrumb-item active">Disciplinary Records</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Disciplinary Records - {{ $student->first_name }} {{ $student->last_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Student</a>
      <a href="{{ route('students.disciplinary-records.create', $student) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Record</a>
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
            <th>Severity</th>
            <th>Action Taken</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($records as $record)
            <tr>
              <td>{{ $record->incident_date->format('M d, Y') }}</td>
              <td>{{ $record->incident_type }}</td>
              <td>
                @php
                  $severityColors = ['minor' => 'secondary', 'moderate' => 'warning', 'major' => 'danger', 'severe' => 'dark'];
                @endphp
                <span class="badge bg-{{ $severityColors[$record->severity] ?? 'secondary' }}">{{ ucfirst($record->severity) }}</span>
              </td>
              <td>{{ $record->action_taken ? ucfirst(str_replace('_', ' ', $record->action_taken)) : '—' }}</td>
              <td>
                @if($record->resolved)
                  <span class="badge bg-success">Resolved</span>
                @else
                  <span class="badge bg-warning">Pending</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('students.disciplinary-records.show', [$student, $record]) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                  <a href="{{ route('students.disciplinary-records.edit', [$student, $record]) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                  <form action="{{ route('students.disciplinary-records.destroy', [$student, $record]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this disciplinary record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No disciplinary records found.</td></tr>
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

