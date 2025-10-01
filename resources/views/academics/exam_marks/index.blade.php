@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Exam Marks</h1>

    <a href="{{ route('academics.exam-marks.bulk') }}" class="btn btn-primary mb-3">
        <i class="bi bi-pencil-square"></i> Bulk Entry
    </a>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('academics.exam-marks.index') }}" class="mb-3">
        <select name="exam_id" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
            <option value="">-- Filter by Exam --</option>
            @foreach($exams as $exam)
                <option value="{{ $exam->id }}" @selected($examId==$exam->id)>
                    {{ $exam->name }} ({{ $exam->term->name ?? '' }} {{ $exam->academicYear->year ?? '' }})
                </option>
            @endforeach
        </select>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Score</th>
                    <th>Grade</th>
                    <th>Remark</th>
                    <th>Status</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($marks as $mark)
                    <tr>
                        <td>{{ $mark->student->full_name ?? '' }}</td>
                        <td>{{ $mark->subject->name ?? '' }}</td>
                        <td>{{ $mark->score_raw ?? '-' }}</td>
                        <td>{{ $mark->grade_label }}</td>
                        <td>{{ $mark->remark ?? '-' }}</td>
                        <td><span class="badge bg-info">{{ ucfirst($mark->status) }}</span></td>
                        <td>
                            <a href="{{ route('academics.exam-marks.edit',$mark) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">No marks available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $marks->links() }}
</div>
@endsection
