@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item active">Academic History</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Academic History - {{ $student->full_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Student</a>
      <a href="{{ route('students.academic-history.create', $student) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Entry</a>
    </div>
  </div>

  @include('students.partials.alerts')

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Enrollment Date</th>
            <th>Class</th>
            <th>Stream</th>
            <th>Status</th>
            <th>Final Grade</th>
            <th>Position</th>
            <th>Current</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($history as $entry)
            <tr>
              <td>{{ $entry->enrollment_date->format('M d, Y') }}</td>
              <td>{{ $entry->classroom->name ?? '—' }}</td>
              <td>{{ $entry->stream->name ?? '—' }}</td>
              <td>
                @if($entry->promotion_status)
                  <span class="badge bg-info">{{ ucfirst($entry->promotion_status) }}</span>
                @else
                  —
                @endif
              </td>
              <td>{{ $entry->final_grade ?? '—' }}</td>
              <td>
                @if($entry->class_position)
                  Class: {{ $entry->class_position }}
                  @if($entry->stream_position) / Stream: {{ $entry->stream_position }} @endif
                @else
                  —
                @endif
              </td>
              <td>
                @if($entry->is_current)
                  <span class="badge bg-success">Current</span>
                @else
                  <span class="badge bg-secondary">Past</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('students.academic-history.show', [$student, $entry]) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                  <a href="{{ route('students.academic-history.edit', [$student, $entry]) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                  <form action="{{ route('students.academic-history.destroy', [$student, $entry]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this academic history entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No academic history found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($history->hasPages())
    <div class="card-footer">
      {{ $history->links() }}
    </div>
    @endif
  </div>
</div>
@endsection

