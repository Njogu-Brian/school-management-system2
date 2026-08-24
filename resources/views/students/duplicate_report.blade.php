@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Duplicate admissions' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Duplicate admissions</h1>
        <p class="text-muted mb-0">Find the same child entered more than once — from manual add, bulk import, or online admission.</p>
      </div>
      <a href="{{ route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to students
      </a>
    </div>

    <div class="alert alert-soft border-0 mb-3">
      <i class="bi bi-info-circle"></i>
      Matches use NEMIS, KNEC assessment number, admission number, then name + date of birth (and gender when present).
      Archived and alumni records are included so a re-admission is not mistaken for a new child.
      Same-name twins can look like a match — open both records before merging or archiving.
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Student register groups</div>
            <h3 class="mb-0">{{ count($studentGroups) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Applications matching a student</div>
            <h3 class="mb-0">{{ count($applicationMatches) }}</h3>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="settings-card h-100">
          <div class="card-body">
            <div class="text-muted text-uppercase small">Repeat open applications</div>
            <h3 class="mb-0">{{ count($duplicateApplications) }}</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-people"></i> Students that look like the same child</h5>
      </div>
      <div class="card-body">
        @forelse($studentGroups as $group)
          <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
              <span class="badge {{ ($group['confidence'] ?? '') === 'high' ? 'bg-danger' : 'bg-warning text-dark' }}">
                {{ $group['reason_label'] }}
              </span>
              @foreach(($group['reasons'] ?? []) as $reason)
                @if($reason !== $group['reason_label'])
                  <span class="badge bg-light text-dark">{{ $reason }}</span>
                @endif
              @endforeach
            </div>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Admission #</th>
                    <th>Name</th>
                    <th>DOB</th>
                    <th>Gender</th>
                    <th>NEMIS</th>
                    <th>Class</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($group['students'] as $stu)
                    <tr>
                      <td><code>{{ $stu['admission_number'] ?? '—' }}</code></td>
                      <td class="fw-semibold">{{ $stu['full_name'] }}</td>
                      <td>{{ $stu['dob'] ?? '—' }}</td>
                      <td class="text-capitalize">{{ $stu['gender'] ?? '—' }}</td>
                      <td>{{ $stu['nemis_number'] ?: '—' }}</td>
                      <td>{{ $stu['classroom'] ?? '—' }}</td>
                      <td><span class="badge bg-secondary">{{ $stu['status'] }}</span></td>
                      <td>
                        @if(!empty($stu['url']))
                          <a href="{{ $stu['url'] }}" class="btn btn-sm btn-outline-primary">Open</a>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @empty
          <p class="text-muted mb-0">No duplicate groups in the student register.</p>
        @endforelse
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-globe"></i> Open applications that match an existing student</h5>
      </div>
      <div class="card-body">
        @forelse($applicationMatches as $row)
          <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-semibold">{{ $row['full_name'] }}</div>
                <div class="small text-muted">
                  {{ $row['source_label'] }}
                  @if(!empty($row['application_no'])) · {{ $row['application_no'] }} @endif
                  @if(!empty($row['status'])) · {{ str_replace('_', ' ', $row['status']) }} @endif
                </div>
              </div>
              @if(!empty($row['url']))
                <a href="{{ $row['url'] }}" class="btn btn-sm btn-outline-primary">Review application</a>
              @endif
            </div>
            @include('students.partials.duplicate_matches', ['matches' => $row['matches']])
          </div>
        @empty
          <p class="text-muted mb-0">No open applications match a student already on the register.</p>
        @endforelse
      </div>
    </div>

    <div class="settings-card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-files"></i> Repeat applications of the same child</h5>
      </div>
      <div class="card-body">
        @forelse($duplicateApplications as $group)
          <div class="border rounded-3 p-3 mb-3">
            <div class="fw-semibold mb-2">{{ $group['reason_label'] }}</div>
            <ul class="mb-0">
              @foreach($group['applications'] as $app)
                <li class="mb-1">
                  @if(!empty($app['url']))
                    <a href="{{ $app['url'] }}">{{ $app['label'] }}</a>
                  @else
                    {{ $app['label'] }}
                  @endif
                  <span class="text-muted small">
                    · {{ str_replace('_', ' ', $app['status'] ?? '') }}
                    @if(!empty($app['submitted'])) · {{ $app['submitted'] }} @endif
                  </span>
                </li>
              @endforeach
            </ul>
          </div>
        @empty
          <p class="text-muted mb-0">No repeat open applications were found.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
