@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Attendance Reports</h4>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('attendance.records') }}" class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Class</label>
            <select name="class" class="form-select">
                <option value="">-- All --</option>
                @foreach($classes as $id => $name)
                    <option value="{{ $id }}" {{ $selectedClass == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Stream</label>
            <select name="stream" class="form-select">
                <option value="">-- All --</option>
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
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    {{-- Records --}}
    @if($records->isEmpty())
        <div class="alert alert-info">No attendance records found for the selected period.</div>
    @else
        @foreach($records as $date => $list)
            <h6 class="mt-4">{{ $date }}</h6>
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Admission #</th>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Stream</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $att)
                        <tr>
                            <td>{{ $att->student->admission_number }}</td>
                            <td>{{ $att->student->full_name }}</td>
                            <td>{{ $att->student->classroom->name ?? '' }}</td>
                            <td>{{ $att->student->stream->name ?? '' }}</td>
                            <td>
                                @if($att->status == 'present') ✅ Present
                                @elseif($att->status == 'absent') ❌ Absent
                                @else ⏱️ Late
                                @endif
                            </td>
                            <td>{{ $att->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</div>
@endsection
