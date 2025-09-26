@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Report Card - {{ $reportCard->student->full_name }}</h1>

    <p><strong>Classroom:</strong> {{ $reportCard->classroom->name }}</p>
    <p><strong>Term:</strong> {{ $reportCard->term->name }}</p>
    <p><strong>Year:</strong> {{ $reportCard->academicYear->year }}</p>
    <p><strong>Status:</strong> {{ ucfirst($reportCard->status) }}</p>

    <hr>
    <h3>Subjects</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Score</th>
                <th>Grade</th>
                <th>Teacher Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reportCard->marks as $mark)
            <tr>
                <td>{{ $mark->subject->name }}</td>
                <td>{{ $mark->score_moderated ?? $mark->score_raw }}</td>
                <td>{{ $mark->grade_label }}</td>
                <td>{{ $mark->remark }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <a href="{{ route('report-cards.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
s