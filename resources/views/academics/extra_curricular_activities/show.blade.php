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
                        @if($extra_curricular_activity->fee_amount)
                        <tr>
                            <th>Fee Amount:</th>
                            <td><strong>KES {{ number_format($extra_curricular_activity->fee_amount, 2) }}</strong></td>
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
            <div class="card shadow-sm mb-4">
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

            @if($extra_curricular_activity->fee_amount)
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-currency-dollar"></i> Finance Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Fee Amount:</th>
                            <td><strong>KES {{ number_format($extra_curricular_activity->fee_amount, 2) }}</strong></td>
                        </tr>
                        @if($extra_curricular_activity->votehead)
                        <tr>
                            <th>Votehead:</th>
                            <td>
                                <span class="badge bg-info">{{ $extra_curricular_activity->votehead->name }}</span>
                                @if(Route::has('finance.voteheads.show'))
                                <a href="{{ route('finance.voteheads.show', $extra_curricular_activity->votehead) }}" class="btn btn-sm btn-outline-primary ms-2">
                                    <i class="bi bi-box-arrow-up-right"></i> View
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th>Auto-invoice:</th>
                            <td>
                                <span class="badge bg-{{ $extra_curricular_activity->auto_invoice ? 'success' : 'secondary' }}">
                                    {{ $extra_curricular_activity->auto_invoice ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            @php
                $assignedStudents = $extra_curricular_activity->students();
            @endphp
            @if($assignedStudents->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assigned Students ({{ $assignedStudents->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignedStudents as $student)
                                <tr>
                                    <td>{{ $student->first_name }} {{ $student->last_name }}</td>
                                    <td>{{ $student->classroom->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $optionalFee = \App\Models\OptionalFee::where('student_id', $student->id)
                                                ->where('votehead_id', $extra_curricular_activity->votehead_id)
                                                ->first();
                                        @endphp
                                        @if($optionalFee)
                                            <span class="badge bg-{{ $optionalFee->status == 'billed' ? 'warning' : 'success' }}">
                                                {{ ucfirst($optionalFee->status) }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Not Invoiced</span>
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
    </div>
</div>
@endsection

