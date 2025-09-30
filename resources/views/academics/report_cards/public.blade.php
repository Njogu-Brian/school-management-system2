@extends('layouts.app')
@section('content')
<div class="container">
  <h1>{{ $report->student->full_name }} – Report Card</h1>
  <p>Class: {{ optional($report->classroom)->name }} | Term/Year: {{ optional($report->term)->name }} / {{ optional($report->academicYear)->year }}</p>
  <a class="btn btn-outline-secondary mb-3" target="_blank" href="{{ $report->pdf_path ? Storage::disk('public')->url($report->pdf_path) : '#' }}">Open PDF</a>
  @include('academics.report_cards.show', ['report_card'=>$report])
</div>
@endsection
