@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Exam Timetable</h1>

  @forelse($exams as $day => $list)
    <div class="card mb-3">
      <div class="card-header fw-bold">{{ \Carbon\Carbon::parse($day)->format('l, d M Y') }}</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Time</th>
              <th>Exam</th>
              <th>Subject</th>
              <th>Classroom</th>
              <th>Term / Year</th>
            </tr>
          </thead>
          <tbody>
          @foreach($list as $exam)
            <tr>
              <td>
                {{ optional($exam->starts_on)->format('H:i') }}
                @if($exam->ends_on) – {{ $exam->ends_on->format('H:i') }} @endif
              </td>
              <td>{{ $exam->name }} <span class="badge text-bg-secondary text-uppercase">{{ $exam->type }}</span></td>
              <td>{{ optional($exam->subject)->name }}</td>
              <td>{{ optional($exam->classroom)->name }}</td>
              <td>{{ optional($exam->term)->name }} / {{ optional($exam->academicYear)->year }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="alert alert-info">No scheduled exams.</div>
  @endforelse
</div>
@endsection
