@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Activities</div>
        <h1 class="mb-1">{{ $extra_curricular_activity->name }}</h1>
        <p class="text-muted mb-0">Activity details, finance, staff, and students.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.extra-curricular-activities.edit', $extra_curricular_activity) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Activity Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Name</th><td>{{ $extra_curricular_activity->name }}</td></tr>
              <tr><th>Type</th><td><span class="pill-badge pill-info">{{ ucfirst($extra_curricular_activity->type) }}</span></td></tr>
              <tr><th>Day</th><td>{{ $extra_curricular_activity->day ?? 'N/A' }}</td></tr>
              <tr><th>Time</th><td>@if($extra_curricular_activity->start_time) {{ $extra_curricular_activity->start_time->format('H:i') }} - {{ $extra_curricular_activity->end_time->format('H:i') }} @else N/A @endif</td></tr>
              <tr><th>Period</th><td>{{ $extra_curricular_activity->period ?? 'N/A' }}</td></tr>
              <tr><th>Academic Year</th><td>{{ $extra_curricular_activity->academicYear->year ?? 'N/A' }}</td></tr>
              <tr><th>Term</th><td>{{ $extra_curricular_activity->term->name ?? 'N/A' }}</td></tr>
              <tr><th>Status</th><td><span class="pill-badge pill-{{ $extra_curricular_activity->is_active ? 'success' : 'muted' }}">{{ $extra_curricular_activity->is_active ? 'Active' : 'Inactive' }}</span></td></tr>
              <tr><th>Repeat Weekly</th><td><span class="pill-badge pill-{{ $extra_curricular_activity->repeat_weekly ? 'info' : 'muted' }}">{{ $extra_curricular_activity->repeat_weekly ? 'Yes' : 'No' }}</span></td></tr>
              @if($extra_curricular_activity->description)<tr><th>Description</th><td>{{ $extra_curricular_activity->description }}</td></tr>@endif
              @if($extra_curricular_activity->fee_amount)<tr><th>Fee Amount</th><td><strong>KES {{ number_format($extra_curricular_activity->fee_amount, 2) }}</strong></td></tr>@endif
            </table>
          </div>
        </div>

        @if($extra_curricular_activity->classrooms()->count() > 0)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Classrooms ({{ $extra_curricular_activity->classrooms()->count() }})</h5></div>
          <div class="card-body"><div class="d-flex flex-wrap gap-2">@foreach($extra_curricular_activity->classrooms() as $classroom)<span class="pill-badge pill-primary">{{ $classroom->name }}</span>@endforeach</div></div>
        </div>
        @endif

        @if($extra_curricular_activity->staff()->count() > 0)
        <div class="settings-card mb-3">
          <div class="card-header"><h5 class="mb-0">Supervising Staff ({{ $extra_curricular_activity->staff()->count() }})</h5></div>
          <div class="card-body"><div class="d-flex flex-wrap gap-2">@foreach($extra_curricular_activity->staff() as $member)<span class="pill-badge pill-success">{{ $member->full_name }}</span>@endforeach</div></div>
        </div>
        @endif

        @if($extra_curricular_activity->fee_amount)
        <div class="settings-card mb-3 border-warning-subtle">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-currency-dollar"></i><h5 class="mb-0">Finance Information</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="200">Fee Amount</th><td><strong>KES {{ number_format($extra_curricular_activity->fee_amount, 2) }}</strong></td></tr>
              @if($extra_curricular_activity->votehead)
              <tr><th>Votehead</th><td><span class="pill-badge pill-info">{{ $extra_curricular_activity->votehead->name }}</span>@if(Route::has('finance.voteheads.show'))<a href="{{ route('finance.voteheads.show', $extra_curricular_activity->votehead) }}" class="btn btn-sm btn-ghost-strong text-primary ms-2"><i class="bi bi-box-arrow-up-right"></i> View</a>@endif</td></tr>
              @endif
              <tr><th>Auto-invoice</th><td><span class="pill-badge pill-{{ $extra_curricular_activity->auto_invoice ? 'success' : 'muted' }}">{{ $extra_curricular_activity->auto_invoice ? 'Enabled' : 'Disabled' }}</span></td></tr>
            </table>
          </div>
        </div>
        @endif

        @php $assignedStudents = $extra_curricular_activity->students(); @endphp
        @if($assignedStudents->count() > 0)
        <div class="settings-card">
          <div class="card-header"><h5 class="mb-0">Assigned Students ({{ $assignedStudents->count() }})</h5></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Name</th><th>Class</th><th>Status</th></tr></thead>
                <tbody>
                  @foreach($assignedStudents as $student)
                  @php $optionalFee = \App\Models\OptionalFee::where('student_id', $student->id)->where('votehead_id', $extra_curricular_activity->votehead_id)->first(); @endphp
                  <tr>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                    <td>
                      @if($optionalFee)
                        <span class="pill-badge pill-{{ $optionalFee->status == 'billed' ? 'warning' : 'success' }}">{{ ucfirst($optionalFee->status) }}</span>
                      @else
                        <span class="pill-badge pill-muted">Not Invoiced</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Information</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Academic Year:</strong> {{ $extra_curricular_activity->academicYear->year ?? 'N/A' }}</small>
            <small class="text-muted d-block mb-1"><strong>Term:</strong> {{ $extra_curricular_activity->term->name ?? 'N/A' }}</small>
            @if($extra_curricular_activity->assessor)
              <small class="text-muted d-block mb-1"><strong>Assessed by:</strong> {{ $extra_curricular_activity->assessor->full_name }}</small>
            @endif
            <small class="text-muted d-block"><strong>Created:</strong> {{ $extra_curricular_activity->created_at->format('d M Y') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
