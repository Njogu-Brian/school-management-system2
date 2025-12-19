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
        <h1 class="mb-1">Notification Recipients</h1>
        <p class="text-muted mb-0">Who receives attendance alerts.</p>
      </div>
      <a href="{{ route('attendance.notifications.create') }}" class="btn btn-settings-primary">
        <i class="bi bi-plus-circle"></i> Add Recipient
      </a>
    </div>

    @if($recipients->isEmpty())
      <div class="alert alert-soft border-0">No recipients found. Add one above.</div>
    @else
      <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="mb-0">Recipients</h5>
          <span class="input-chip">{{ $recipients->count() }} total</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Label</th>
                  <th>Staff</th>
                  <th>Assigned Classes</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recipients as $recipient)
                  <tr>
                    <td class="fw-semibold">{{ $recipient->label }}</td>
                    <td>
                      {{ optional($recipient->staff)->first_name }}
                      {{ optional($recipient->staff)->last_name }}
                      ({{ optional($recipient->staff)->phone_number }})
                    </td>
                    <td>
                      @if(!empty($recipient->classrooms_ids))
                        @foreach($recipient->classrooms_ids as $classId)
                          <span class="pill-badge pill-secondary">
                            {{ $classrooms[$classId] ?? 'Unknown' }}
                          </span>
                        @endforeach
                      @else
                        <span class="text-muted">All Classes</span>
                      @endif
                    </td>
                    <td>
                      <span class="pill-badge {{ $recipient->active ? 'pill-success' : 'pill-danger' }}">
                        {{ $recipient->active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="text-end d-flex justify-content-end gap-2">
                      <a href="{{ route('attendance.notifications.edit', $recipient->id) }}" class="btn btn-sm btn-ghost-strong">
                        <i class="bi bi-pencil"></i> Edit
                      </a>
                      <form action="{{ route('attendance.notifications.destroy', $recipient->id) }}" method="POST" class="d-inline">
                        @csrf 
                        @method('DELETE')
                        <button class="btn btn-sm btn-ghost-strong text-danger" onclick="return confirm('Delete this recipient?')">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
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
</div>
@endsection
