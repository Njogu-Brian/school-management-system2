@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Parents Contact' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Parents Contact Information</h1>
        <p class="text-muted mb-0">Child name, class, admission number, father and mother name, phone, email, WhatsApp.</p>
      </div>
      <a href="{{ route('students.index') }}" class="btn btn-ghost-strong"><i class="bi bi-people"></i> Student Details</a>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Filter by class, name, or admission number.</p>
        </div>
      </div>
      <div class="card-body">
        <form class="row g-2" method="GET" action="{{ route('students.parents-contact') }}">
          <div class="col-md-2">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classes</option>
              @foreach ($classrooms as $c)
                <option value="{{ $c->id }}" @selected(request('classroom_id') == $c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Name</label>
            <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Student name">
          </div>
          <div class="col-md-2">
            <label class="form-label">Admission #</label>
            <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission #">
          </div>
          <div class="col-md-2">
            <label class="form-label">Per Page</label>
            <select name="per_page" class="form-select">
              @foreach ([10, 20, 50, 100] as $pp)
                <option value="{{ $pp }}" @selected(request('per_page', 20) == $pp)>{{ $pp }}/page</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-settings-primary w-100"><i class="bi bi-funnel"></i> Apply</button>
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <a href="{{ route('students.parents-contact') }}" class="btn btn-ghost-strong w-100">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Child</th>
                <th>Class</th>
                <th>Admission #</th>
                <th>Father name</th>
                <th>Father phone</th>
                <th>Father email</th>
                <th>Father WhatsApp</th>
                <th>Mother name</th>
                <th>Mother phone</th>
                <th>Mother email</th>
                <th>Mother WhatsApp</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                @php $p = $student->parent; @endphp
                <tr>
                  <td>
                    @if(Route::has('students.show'))
                      <a href="{{ route('students.show', $student->id) }}" class="fw-semibold text-reset">{{ $student->full_name }}</a>
                    @else
                      <span class="fw-semibold">{{ $student->full_name }}</span>
                    @endif
                  </td>
                  <td>{{ $student->classroom->name ?? '—' }}</td>
                  <td class="fw-semibold">{{ $student->admission_number ?? '—' }}</td>
                  <td>{{ $p?->father_name ?? '—' }}</td>
                  <td>{{ $p?->father_phone ?? '—' }}</td>
                  <td>{{ $p?->father_email ?? '—' }}</td>
                  <td>{{ $p?->father_whatsapp ?? '—' }}</td>
                  <td>{{ $p?->mother_name ?? '—' }}</td>
                  <td>{{ $p?->mother_phone ?? '—' }}</td>
                  <td>{{ $p?->mother_email ?? '—' }}</td>
                  <td>{{ $p?->mother_whatsapp ?? '—' }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="11" class="text-center text-muted py-4">No students found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="text-muted small">
            Showing {{ $students->firstItem() ?? 0 }}–{{ $students->lastItem() ?? 0 }} of {{ $students->total() }}
          </div>
          {{ $students->withQueryString()->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
