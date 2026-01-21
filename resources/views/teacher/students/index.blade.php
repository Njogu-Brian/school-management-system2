@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">My Students</h2>
      <small class="text-muted">View students in your assigned classes</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Classroom</label>
          <select name="classroom_id" class="form-select">
            <option value="">All Classes</option>
            @foreach($classrooms as $classroom)
              <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Search</label>
          <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or Admission Number">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filter
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- Students Table --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-people"></i> Students ({{ $students->total() }})</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Admission #</th>
              <th>Name</th>
              <th>Class</th>
              <th>Stream</th>
              <th>Parent Contact</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($students as $student)
              <tr>
                <td><span class="badge bg-secondary">{{ $student->admission_number }}</span></td>
                <td>
                  <div class="fw-semibold">{{ $student->full_name }}</div>
                  <small class="text-muted">{{ $student->gender }}</small>
                </td>
                <td>{{ $student->classroom->name ?? '—' }}</td>
                <td>{{ $student->stream->name ?? '—' }}</td>
                <td>
                  <div class="fw-semibold">{{ $student->parent?->primary_contact_name ?? '—' }}</div>
                  <small class="text-muted">
                    {{ $student->parent?->primary_contact_phone ?? $student->parent?->primary_contact_email ?? 'No contact on record' }}
                  </small>
                </td>
                <td class="text-end">
                  <a href="{{ route('teacher.students.show', $student) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View Details
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No students found in your assigned classes.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($students->hasPages())
      <div class="card-footer">
        {{ $students->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

