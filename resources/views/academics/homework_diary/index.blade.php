@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Homework Diary</h1>
        <p class="text-muted mb-0">Track submissions, marking, and status.</p>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select">
              <option value="">All Students</option>
              @foreach($students as $student)
                <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Homework</label>
            <select name="homework_id" class="form-select">
              <option value="">All Homework</option>
              @foreach($homeworks as $hw)
                <option value="{{ $hw->id }}" {{ request('homework_id') == $hw->id ? 'selected' : '' }}>{{ $hw->title }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
              <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
              <option value="marked" {{ request('status') == 'marked' ? 'selected' : '' }}>Marked</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">From Date</label>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">To Date</label>
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                <th>Homework</th>
                <th>Subject</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Score</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($homeworkDiary as $entry)
                <tr>
                  <td>{{ $entry->student->full_name ?? '' }}</td>
                  <td>{{ $entry->homework->title ?? '' }}</td>
                  <td>{{ $entry->homework->subject->name ?? '' }}</td>
                  <td>{{ $entry->homework->due_date ? $entry->homework->due_date->format('d M Y') : 'N/A' }}</td>
                  <td>
                    <span class="pill-badge pill-{{ $entry->status == 'marked' ? 'success' : ($entry->status == 'submitted' ? 'info' : 'warning') }}">{{ ucfirst($entry->status) }}</span>
                  </td>
                  <td>
                    @if($entry->score !== null && $entry->max_score !== null && $entry->max_score > 0)
                      <span class="pill-badge pill-primary">{{ $entry->score }}/{{ $entry->max_score }}</span>
                      <div class="small text-muted">{{ number_format($entry->percentage, 1) }}%</div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <a href="{{ route('academics.homework-diary.show', $entry) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No homework diary entries found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $homeworkDiary->links() }}</div>
    </div>
  </div>
</div>
@endsection
