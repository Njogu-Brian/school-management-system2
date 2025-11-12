@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $extra_curricular_activity->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.extra-curricular-activities.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.extra-curricular-activities.edit', $extra_curricular_activity) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Activity Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Name:</th>
                            <td>{{ $extra_curricular_activity->name }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($extra_curricular_activity->type) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Day:</th>
                            <td>{{ $extra_curricular_activity->day ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Time:</th>
                            <td>
                                @if($extra_curricular_activity->start_time)
                                    {{ $extra_curricular_activity->start_time->format('H:i') }} - {{ $extra_curricular_activity->end_time->format('H:i') }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Period:</th>
                            <td>{{ $extra_curricular_activity->period ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Academic Year:</th>
                            <td>{{ $extra_curricular_activity->academicYear->year ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Term:</th>
                            <td>{{ $extra_curricular_activity->term->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $extra_curricular_activity->is_active ? 'success' : 'secondary' }}">
                                    {{ $extra_curricular_activity->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Repeat Weekly:</th>
                            <td>
                                <span class="badge bg-{{ $extra_curricular_activity->repeat_weekly ? 'info' : 'secondary' }}">
                                    {{ $extra_curricular_activity->repeat_weekly ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        @if($extra_curricular_activity->description)
                        <tr>
                            <th>Description:</th>
                            <td>{{ $extra_curricular_activity->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($extra_curricular_activity->classrooms()->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Classrooms ({{ $extra_curricular_activity->classrooms()->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($extra_curricular_activity->classrooms() as $classroom)
                            <span class="badge bg-primary">{{ $classroom->name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($extra_curricular_activity->staff()->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Supervising Staff ({{ $extra_curricular_activity->staff()->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($extra_curricular_activity->staff() as $member)
                            <span class="badge bg-success">{{ $member->full_name }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

