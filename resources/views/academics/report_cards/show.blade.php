@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Report Card - {{ $report_card->student->full_name }}</h1>

    <div class="mb-3">
        @if($report_card->pdf_path)
            <a href="{{ asset('storage/'.$report_card->pdf_path) }}" target="_blank" class="btn btn-primary">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
        @endif
        @if(!$report_card->locked_at)
            <form action="{{ route('academics.report-cards.publish',$report_card) }}" method="POST" class="d-inline">
                @csrf
                <button class="btn btn-success"><i class="bi bi-upload"></i> Publish</button>
            </form>
        @endif
    </div>

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
