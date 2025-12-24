@extends('layouts.app')

@section('page-title', 'Archived Students')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-1">
          <li class="breadcrumb-item"><a href="{{ route('students.index') }}">Students</a></li>
          <li class="breadcrumb-item active" aria-current="page">Archived</li>
        </ol>
      </nav>
      <h4 class="mb-0">Archived Students</h4>
      <p class="text-muted small mb-0">View and restore archived student records.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Students
      </a>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">
      <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
          <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Search by name">
        </div>
        <div class="col-md-3">
          <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission number">
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
          <a href="{{ route('students.archived') }}" class="btn btn-outline-secondary w-100">Clear</a>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>Admission #</th>
              <th>Name</th>
              <th>Class</th>
              <th>Stream</th>
              <th>Archived At</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          @forelse($students as $s)
            <tr>
              <td class="fw-semibold">{{ $s->admission_number }}</td>
              <td>{{ $s->first_name }} {{ $s->last_name }}</td>
              <td>{{ $s->classroom->name ?? '—' }}</td>
              <td>{{ $s->stream->name ?? '—' }}</td>
              <td>{{ $s->archived_at?->format('Y-m-d H:i') ?? '—' }}</td>
              <td class="text-end">
                <form action="{{ route('students.restore', $s->id) }}" method="POST" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">
                <div class="fw-semibold mb-1">No archived students</div>
                <div class="small">Archived students will appear here when available.</div>
              </td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end">
        {{ $students->links() }}
      </div>
    </div>
  </div>
</div>
@endsection

