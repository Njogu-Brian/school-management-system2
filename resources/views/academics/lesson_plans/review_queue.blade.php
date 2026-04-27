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
        <h1 class="mb-1">Review Queue</h1>
        <p class="text-muted mb-0">Submitted lesson plans awaiting approval.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.lesson-plans.analytics') }}" class="btn btn-settings-primary"><i class="bi bi-graph-up"></i> Analytics</a>
      </div>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classrooms</option>
              @foreach($classrooms as $classroom)
                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>{{ $classroom->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-select">
              <option value="">All Subjects</option>
              @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
              @endforeach
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
                <th>Teacher</th>
                <th>Title</th>
                <th>Subject</th>
                <th>Classroom</th>
                <th>Planned</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($lessonPlans as $plan)
                <tr>
                  <td class="fw-semibold">{{ $plan->creator->full_name ?? '-' }}</td>
                  <td>{{ $plan->title }}</td>
                  <td>{{ $plan->subject->name ?? '-' }}</td>
                  <td>{{ $plan->classroom->name ?? '-' }}</td>
                  <td>{{ optional($plan->planned_date)->format('d M Y') }}</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      <button class="btn btn-sm btn-ghost-strong text-success" type="button" data-bs-toggle="modal" data-bs-target="#approveModal{{ $plan->id }}"><i class="bi bi-check2-circle"></i></button>
                      <button class="btn btn-sm btn-ghost-strong text-danger" type="button" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $plan->id }}"><i class="bi bi-x-circle"></i></button>
                    </div>
                  </td>
                </tr>

                <div class="modal fade" id="approveModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST" action="{{ route('academics.lesson-plans.approve', $plan) }}">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Approve lesson plan</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <label class="form-label">Approval notes (optional)</label>
                          <textarea class="form-control" name="approval_notes" rows="3" maxlength="1000"></textarea>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-settings-primary">Approve</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <div class="modal fade" id="rejectModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <form method="POST" action="{{ route('academics.lesson-plans.reject', $plan) }}">
                        @csrf
                        <div class="modal-header">
                          <h5 class="modal-title">Reject lesson plan</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                          <label class="form-label">Rejection notes</label>
                          <textarea class="form-control" name="rejection_notes" rows="3" maxlength="1000" required></textarea>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-danger">Reject</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No submitted lesson plans found</td></tr>
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

