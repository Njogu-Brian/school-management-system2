@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Report Card - {{ $report_card->student->full_name }}</h1>

    <h4>Summary</h4>
    <p>{{ $report_card->summary ?? 'No summary provided.' }}</p>

    <h4>Teacher Remark</h4>
    <p>{{ $report_card->teacher_remark ?? '-' }}</p>

    <h4>Headteacher Remark</h4>
    <p>{{ $report_card->headteacher_remark ?? '-' }}</p>

    <h4>Skills & Personal Growth</h4>
    <ul>
        @foreach($report_card->skills as $skill)
            <li>{{ $skill->skill_name }} - <strong>{{ $skill->rating }}</strong></li>
        @endforeach
    </ul>
</div>
@endsection
