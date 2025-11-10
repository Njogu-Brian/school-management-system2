@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item active">Families</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h1 class="h4 mb-0">Families (Siblings)</h1>
    <div class="d-flex gap-2">
      <form action="{{ route('families.populate') }}" method="POST" class="d-inline" onsubmit="return confirm('This will auto-populate all families with blank fields from their students\' parent records. Continue?')">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm">
          <i class="bi bi-arrow-clockwise"></i> Fix Blank Fields
        </button>
      </form>
      <a href="{{ route('families.link') }}" class="btn btn-success">
        <i class="bi bi-link-45deg"></i> Link Two Students
      </a>
    </div>
  </div>
  <p class="text-muted small mb-3">
    <i class="bi bi-info-circle"></i> Use "Link Two Students" to quickly link siblings, or click "Manage Siblings" to add more students to existing families. 
    Families are created automatically when you link students. Guardian details are pulled from student parent records.
    <strong>Purpose:</strong> Enable family-level fee billing (one fee per family) and sibling discounts.
  </p>

  @include('students.partials.alerts')

  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('families.index') }}" class="row g-2">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="Search by guardian name, phone, or email">
          </div>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Search</button>
        </div>
        @if($q)
        <div class="col-md-2">
          <a href="{{ route('families.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x"></i> Clear</a>
        </div>
        @endif
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Guardian</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Students</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($families as $fam)
            <tr>
              <td class="fw-semibold">#{{ $fam->id }}</td>
              <td>
                <div class="fw-semibold">{{ $fam->guardian_name }}</div>
                @if($fam->phone || $fam->email)
                  <div class="text-muted small">
                    @if($fam->phone) <i class="bi bi-telephone"></i> {{ $fam->phone }} @endif
                    @if($fam->phone && $fam->email) · @endif
                    @if($fam->email) <i class="bi bi-envelope"></i> {{ $fam->email }} @endif
                  </div>
                @endif
              </td>
              <td>{{ $fam->phone ?? '—' }}</td>
              <td>{{ $fam->email ?? '—' }}</td>
              <td>
                @if($fam->students->count() > 0)
                  <div class="vstack gap-1">
                    @foreach($fam->students as $student)
                      <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('students.show', $student->id) }}" class="text-decoration-none">
                          <span class="fw-semibold">{{ $student->first_name }} {{ $student->last_name }}</span>
                          @if($student->admission_number)
                            <small class="text-muted">({{ $student->admission_number }})</small>
                          @endif
                        </a>
                      </div>
                    @endforeach
                  </div>
                  <small class="text-muted mt-1 d-block">
                    <span class="badge bg-secondary">{{ $fam->students_count }} {{ Str::plural('sibling', $fam->students_count) }}</span>
                  </small>
                @else
                  <span class="text-muted">No students</span>
                @endif
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-primary" href="{{ route('families.manage', $fam) }}">
                  <i class="bi bi-people"></i> Manage Siblings
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No families found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $families->links() }}
    </div>
  </div>
</div>
@endsection
