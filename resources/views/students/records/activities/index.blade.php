@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
      <li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
      <li class="breadcrumb-item active">Extracurricular Activities</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Extracurricular Activities - {{ $student->full_name }}</h1>
    <div class="d-flex gap-2">
      <a href="{{ route('students.show', $student) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Student</a>
      <a href="{{ route('students.activities.create', $student) }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Activity</a>
    </div>
  </div>

  @include('students.partials.alerts')

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Activity</th>
            <th>Type</th>
            <th>Period</th>
            <th>Role/Position</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($activities as $activity)
            <tr>
              <td class="fw-semibold">{{ $activity->activity_name }}</td>
              <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $activity->activity_type)) }}</span></td>
              <td>
                {{ $activity->start_date->format('M Y') }}
                @if($activity->end_date) - {{ $activity->end_date->format('M Y') }} @else <span class="text-muted">(Ongoing)</span> @endif
              </td>
              <td>{{ $activity->position_role ?? '—' }}</td>
              <td>
                @if($activity->is_active)
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">Inactive</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('students.activities.show', [$student, $activity]) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                  <a href="{{ route('students.activities.edit', [$student, $activity]) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                  <form action="{{ route('students.activities.destroy', [$student, $activity]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this activity?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No extracurricular activities found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if($activities->hasPages())
    <div class="card-footer">
      {{ $activities->links() }}
    </div>
    @endif
  </div>
</div>
@endsection

