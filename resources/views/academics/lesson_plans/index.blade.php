@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Lesson Plans</div>
        <h1 class="mb-1">Lesson Plans</h1>
        <p class="text-muted mb-0">Plan, track, and approve lessons.</p>
      </div>
      <a href="{{ route('academics.lesson-plans.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Create Lesson Plan</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classrooms</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">All Subjects</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All Status</option>
              <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Planned</option>
              <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
              <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">From</label>
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label">To</label>
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Filter</button>
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
                <th>Title</th>
                <th>Subject</th>
                <th>Classroom</th>
                <th>Planned Date</th>
                <th>Status</th>
                <th>Approval</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($lessonPlans as $plan)
                <tr>
                  <td class="fw-semibold">{{ $plan->title }}</td>
                  <td>{{ $plan->subject->name }}</td>
                  <td>{{ $plan->classroom->name }}</td>
                  <td>{{ $plan->planned_date->format('d M Y') }}</td>
                  <td><span class="pill-badge pill-{{ $plan->status == 'completed' ? 'success' : ($plan->status == 'in_progress' ? 'warning' : 'info') }}">{{ ucfirst($plan->status) }}</span></td>
                  <td>
                    @if($plan->isApproved())
                      <span class="pill-badge pill-success"><i class="bi bi-check-circle"></i> Approved</span>
                      <div class="small text-muted">{{ $plan->approved_at->format('d M Y') }}</div>
                    @else
                      <span class="pill-badge pill-warning"><i class="bi bi-clock"></i> Pending</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      <a href="{{ route('academics.lesson-plans.edit', $plan) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No lesson plans found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $lessonPlans->links() }}</div>
    </div>
  </div>
</div>
@endsection
