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
        <h1 class="mb-1">Activities</h1>
        <p class="text-muted mb-0">Manage clubs, sports, and events.</p>
      </div>
      <a href="{{ route('academics.extra-curricular-activities.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Activity</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select">
              <option value="">All Years</option>
              @foreach($years as $year)
                <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select">
              <option value="">All Terms</option>
              @foreach($terms as $term)
                <option value="{{ $term->id }}" {{ request('term_id') == $term->id ? 'selected' : '' }}>{{ $term->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
              <option value="">All Types</option>
              <option value="club" {{ request('type') == 'club' ? 'selected' : '' }}>Club</option>
              <option value="sport" {{ request('type') == 'sport' ? 'selected' : '' }}>Sport</option>
              <option value="event" {{ request('type') == 'event' ? 'selected' : '' }}>Event</option>
              <option value="parade" {{ request('type') == 'parade' ? 'selected' : '' }}>Parade</option>
              <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="is_active" class="form-select">
              <option value="">All</option>
              <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
              <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
            </select>
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <div class="d-flex gap-2 w-100">
              <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
              <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-ghost-strong" title="Clear"><i class="bi bi-x-lg"></i></a>
            </div>
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
                <th>Name</th>
                <th>Type</th>
                <th>Day/Time</th>
                <th>Academic Year</th>
                <th>Term</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($activities as $activity)
              <tr>
                <td class="fw-semibold">{{ $activity->name }}</td>
                <td><span class="pill-badge pill-info">{{ ucfirst($activity->type) }}</span></td>
                <td>
                  @if($activity->day)
                    {{ $activity->day }}
                    @if($activity->start_time)
                      ({{ $activity->start_time->format('H:i') }} - {{ $activity->end_time->format('H:i') }})
                    @endif
                  @else
                    N/A
                  @endif
                </td>
                <td>{{ $activity->academicYear->year ?? 'N/A' }}</td>
                <td>{{ $activity->term->name ?? 'N/A' }}</td>
                <td><span class="pill-badge pill-{{ $activity->is_active ? 'success' : 'muted' }}">{{ $activity->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    <a href="{{ route('academics.extra-curricular-activities.show', $activity) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                    <a href="{{ route('academics.extra-curricular-activities.edit', $activity) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('academics.extra-curricular-activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="7" class="text-center text-muted py-4">No activities found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $activities->links() }}</div>
    </div>
  </div>
</div>
@endsection
