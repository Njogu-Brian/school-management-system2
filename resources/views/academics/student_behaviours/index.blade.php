@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Behaviour</div>
        <h1 class="mb-1">Student Behaviour Records</h1>
        <p class="text-muted mb-0">Log and review behaviour incidents.</p>
      </div>
      <a href="{{ route('academics.student-behaviours.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus"></i> Record Behaviour</a>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                <th>Behaviour</th>
                <th>Term</th>
                <th>Year</th>
                <th>Recorded By</th>
                <th>Notes</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($records as $rec)
                <tr>
                  <td>{{ $rec->student->full_name }}</td>
                  <td>{{ $rec->behaviour->name }}</td>
                  <td>{{ $rec->term->name }}</td>
                  <td>{{ $rec->academicYear->year }}</td>
                  <td>{{ $rec->teacher->full_name ?? 'N/A' }}</td>
                  <td class="text-muted">{{ $rec->notes }}</td>
                  <td class="text-end">
                    <form action="{{ route('academics.student-behaviours.destroy',$rec) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this record?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No behaviour records found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
