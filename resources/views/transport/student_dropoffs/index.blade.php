@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport</p>
                <h1 class="mb-1">Student Drop-off Points</h1>
                <p class="text-muted mb-0">
                    Set morning pickup and evening drop-off. List prices recalculate from route rates — manage fees in Finance.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-geo-alt"></i> Point rates
                </a>
                <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-cash-coin"></i> Transport fees
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
                @if(session('transport_fee_errors'))
                    <ul class="mb-0 mt-2">
                        @foreach(session('transport_fee_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="classroom_id">Classroom</label>
                        <select name="classroom_id" id="classroom_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Select class to load students</option>
                            @foreach($classrooms as $class)
                                <option value="{{ $class->id }}" @selected((int) $classroomId === (int) $class->id)>{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Choose <strong>Own means</strong> when a student does not use school transport for that leg.</small>
                    </div>
                </form>
            </div>
        </div>

        @if($classroomId && $students->isNotEmpty())
            <form method="POST" action="{{ route('transport.student-dropoffs.update') }}">
                @csrf
                <input type="hidden" name="classroom_id" value="{{ $classroomId }}">

                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-0">Students</h5>
                            <small class="text-muted">{{ $students->count() }} student(s)</small>
                        </div>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-save"></i> Save drop-off points
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission</th>
                                        <th>Stream</th>
                                        <th style="min-width:200px;">Morning pickup</th>
                                        <th style="min-width:200px;">Evening drop-off</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $idx => $student)
                                        @php
                                            $assignment = $assignmentMap[$student->id] ?? null;
                                            $morningId = $assignment?->morning_drop_off_point_id
                                                ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                            $eveningId = $assignment?->evening_drop_off_point_id
                                                ?: ($student->drop_off_point_id ?: $ownMeansPointId);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $student->full_name }}
                                                <input type="hidden" name="points[{{ $idx }}][student_id]" value="{{ $student->id }}">
                                            </td>
                                            <td>{{ $student->admission_number }}</td>
                                            <td>{{ optional($student->stream)->name ?? '—' }}</td>
                                            <td>
                                                <select name="points[{{ $idx }}][morning_drop_off_point_id]" class="form-select form-select-sm">
                                                    <option value="{{ $ownMeansPointId }}" @selected((int) $morningId === (int) $ownMeansPointId)>Own means (no morning transport)</option>
                                                    @foreach($dropOffPoints as $point)
                                                        @continue((int) $point->id === (int) $ownMeansPointId)
                                                        <option value="{{ $point->id }}" @selected((int) $morningId === (int) $point->id)>{{ $point->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select name="points[{{ $idx }}][evening_drop_off_point_id]" class="form-select form-select-sm">
                                                    <option value="{{ $ownMeansPointId }}" @selected((int) $eveningId === (int) $ownMeansPointId)>Own means (no evening transport)</option>
                                                    @foreach($dropOffPoints as $point)
                                                        @continue((int) $point->id === (int) $ownMeansPointId)
                                                        <option value="{{ $point->id }}" @selected((int) $eveningId === (int) $point->id)>{{ $point->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        @elseif($classroomId)
            <div class="settings-card">
                <div class="card-body text-center text-muted py-5">No students found in this classroom.</div>
            </div>
        @else
            <div class="settings-card">
                <div class="card-body text-center text-muted py-5">Select a classroom to manage student drop-off points.</div>
            </div>
        @endif
    </div>
</div>
@endsection
