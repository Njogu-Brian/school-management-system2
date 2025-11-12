@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Extra-Curricular Activities</h1>
        <a href="{{ route('academics.extra-curricular-activities.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Activity
        </a>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <select name="academic_year_id" class="form-select">
                        <option value="">All Years</option>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term_id" class="form-select">
                        <option value="">All Terms</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ request('term_id') == $term->id ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
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
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activities Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Day/Time</th>
                            <th>Academic Year</th>
                            <th>Term</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->name }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($activity->type) }}</span>
                            </td>
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
                            <td>
                                <span class="badge bg-{{ $activity->is_active ? 'success' : 'secondary' }}">
                                    {{ $activity->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.extra-curricular-activities.show', $activity) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('academics.extra-curricular-activities.edit', $activity) }}" class="btn btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('academics.extra-curricular-activities.destroy', $activity) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No activities found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $activities->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

