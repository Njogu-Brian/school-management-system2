@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">📊 Attendance Reports</h3>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="class-tab" data-bs-toggle="tab" data-bs-target="#classReport" type="button" role="tab">By Class/Stream</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="student-tab" data-bs-toggle="tab" data-bs-target="#studentReport" type="button" role="tab">By Student</button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- ================= CLASS/STREAM REPORT ================= -->
        <div class="tab-pane fade show active" id="classReport" role="tabpanel">
            <form method="GET" action="{{ route('attendance.records') }}" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select">
                        <option value="">All</option>
                        @foreach($classes as $id => $name)
                            <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stream</label>
                    <select name="stream" class="form-select">
                        <option value="">All</option>
                        @foreach($streams as $id => $name)
                            <option value="{{ $id }}" {{ $selectedStream == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end" value="{{ $endDate }}" class="form-control">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-purple"><i class="bi bi-search"></i> Filter</button>
                </div>
            </form>

            {{-- Records Table --}}
            @forelse($records as $date => $attendances)
                <div class="card mb-3 shadow-sm">
                    <div class="card-header fw-bold">
                        {{ $date }}
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($attendances as $a)
                                    <tr>
                                        <td>{{ $a->student->full_name }}</td>
                                        <td>{{ $a->student->classroom->name ?? '' }}</td>
                                        <td>{{ $a->student->stream->name ?? '' }}</td>
                                        <td>
                                            <span class="badge 
                                                {{ $a->status == 'present' ? 'bg-success' : ($a->status == 'late' ? 'bg-warning' : 'bg-danger') }}">
                                                {{ ucfirst($a->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $a->reason ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">No records found for this period.</div>
            @endforelse
        </div>

        <!-- ================= STUDENT REPORT ================= -->
        <div class="tab-pane fade" id="studentReport" role="tabpanel">
            <form method="GET" action="{{ route('attendance.records') }}" class="row g-3 mb-4">
                <input type="hidden" name="mode" value="student">

                <div class="col-md-6">
                    <label class="form-label">Search Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select Student --</option>
                        @foreach(\App\Models\Student::orderBy('first_name')->get() as $stu)
                            <option value="{{ $stu->id }}" {{ request('student_id') == $stu->id ? 'selected' : '' }}>
                                {{ $stu->full_name }} ({{ $stu->admission_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end" value="{{ $endDate }}" class="form-control">
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-purple"><i class="bi bi-search"></i> View Report</button>
                </div>
            </form>

            {{-- Student Report Table --}}
            @if(request('mode') == 'student' && request('student_id'))
                @php
                    $student = \App\Models\Student::find(request('student_id'));
                    $studentRecords = \App\Models\Attendance::where('student_id', $student->id)
                        ->whereDate('date','>=',$startDate)
                        ->whereDate('date','<=',$endDate)
                        ->orderBy('date','desc')
                        ->get();
                @endphp

                <div class="card shadow-sm">
                    <div class="card-header fw-bold">
                        Attendance for {{ $student->full_name }} ({{ $student->admission_number }})
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studentRecords as $r)
                                    <tr>
                                        <td>{{ $r->date }}</td>
                                        <td>
                                            <span class="badge 
                                                {{ $r->status == 'present' ? 'bg-success' : ($r->status == 'late' ? 'bg-warning' : 'bg-danger') }}">
                                                {{ ucfirst($r->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $r->reason ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No attendance found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .btn-purple {
        background-color: #5a189a;
        color: white;
        border-radius: 6px;
    }
    .btn-purple:hover {
        background-color: #3c096c;
        color: #fff;
    }
</style>
@endpush
