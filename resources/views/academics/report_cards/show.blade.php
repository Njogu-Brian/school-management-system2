@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Report Card – {{ $report_card->student->full_name }}</h1>
  <p><strong>Class:</strong> {{ optional($report_card->classroom)->name }}
     | <strong>Year / Term:</strong> {{ optional($report_card->academicYear)->year }} / {{ optional($report_card->term)->name }}</p>

  <div class="mb-3">
    <a class="btn btn-outline-primary" href="{{ route('academics.report-cards.edit',$report_card) }}">Edit</a>
    @if($report_card->pdf_path)
      <a class="btn btn-outline-secondary" target="_blank" href="{{ Storage::disk('public')->url($report_card->pdf_path) }}">Open PDF</a>
    @endif
  </div>

  <h5>Subjects</h5>
  <table class="table table-sm">
    <thead><tr><th>Subject</th><th>Opener</th><th>Mid</th><th>End</th><th>Band</th><th>Remark</th></tr></thead>
    <tbody>
    @foreach($report_card->marks as $m)
      <tr>
        <td>{{ optional($m->subject)->name }}</td>
        <td>{{ $m->opener_score }}</td>
        <td>{{ $m->midterm_score }}</td>
        <td>{{ $m->endterm_score }}</td>
        <td>{{ $m->grade_label }}</td>
        <td>{{ $m->subject_remark }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

  <h5>Personal Growth & Social Skills</h5>
  <ul class="list-group mb-3">
    @foreach($report_card->skills as $s)
      <li class="list-group-item d-flex justify-content-between">
        <span>{{ $s->skill_name }}</span><strong>{{ $s->rating }}</strong>
      </li>
    @endforeach
  </ul>

  <p><strong>Career of Interest:</strong> {{ $report_card->career_interest }}</p>
  <p><strong>Gifts / Talent Noticed:</strong> {{ $report_card->talent_noticed }}</p>
  <p><strong>Teacher Remark:</strong> {{ $report_card->teacher_remark }}</p>
  <p><strong>Headteacher Remark:</strong> {{ $report_card->headteacher_remark }}</p>
</div>
@endsection
