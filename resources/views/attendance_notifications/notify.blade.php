@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Attendance</div>
        <h1 class="mb-1">Attendance Notification</h1>
        <p class="text-muted mb-0">Send attendance summaries to configured recipients.</p>
      </div>
      <a href="{{ route('attendance.notifications.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Recipients
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form action="{{ route('attendance.notifications.notify.send') }}" method="POST" class="row g-3 align-items-end">
          @csrf
          <div class="col-md-4">
            <label class="form-label fw-semibold">Select Date</label>
            <input type="date" name="date" value="{{ $date ?? now()->toDateString() }}" class="form-control" required>
          </div>
          <div class="col-md-4 d-flex align-items-center">
            <div class="form-check">
              <input type="checkbox" name="force" value="1" class="form-check-input" id="forceSend">
              <label class="form-check-label text-danger fw-semibold" for="forceSend">
                Force Send (ignore incomplete attendance)
              </label>
            </div>
          </div>
          <div class="col-md-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-settings-primary">
              <i class="bi bi-send"></i> Send Notification
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="alert {{ $isComplete ? 'alert-success' : 'alert-warning' }} alert-soft border-0">
      <i class="bi bi-info-circle"></i>
      Attendance is <strong>{{ $isComplete ? 'Complete' : 'NOT Complete' }}</strong> for {{ $date ?? now()->toDateString() }}.
    </div>

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Attendance Summary (Present)</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class</th>
                <th>Present Count</th>
              </tr>
            </thead>
            <tbody>
              @forelse($summaryByClass as $class => $count)
                <tr>
                  <td>{{ $class }}</td>
                  <td><span class="pill-badge pill-success">{{ $count }}</span></td>
                </tr>
              @empty
                <tr>
                  <td colspan="2" class="text-center text-muted py-3">No attendance data found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="settings-card mt-3">
      <div class="card-header">
        <h5 class="mb-0">Recipients</h5>
      </div>
      <div class="card-body">
        @forelse($recipients as $r)
          <p class="mb-2">
            <strong>{{ $r->label }}</strong> → 
            {{ $r->staff->first_name ?? '' }} {{ $r->staff->last_name ?? '' }} 
            ({{ $r->staff->phone_number ?? 'No phone' }})
          </p>
        @empty
          <p class="text-muted mb-0">No recipients defined.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
