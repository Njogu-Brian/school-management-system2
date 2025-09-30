@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Exam Marks</h1>
  <a href="{{ route('academics.exam-marks.bulk') }}" class="btn btn-primary mb-3">Bulk Enter Marks</a>

  <form class="row g-2 mb-3">
    <div class="col-md-4">
      <select name="exam_id" class="form-select" onchange="this.form.submit()">
        <option value="">Filter by Exam...</option>
        @foreach($exams as $e)
          <option value="{{ $e->id }}" @selected(request('exam_id')==$e->id)>{{ $e->name }} ({{ $e->type }})</option>
        @endforeach
      </select>
    </div>
  </form>

  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <table class="table table-sm table-striped">
    <thead><tr>
      <th>Student</th><th>Subject</th><th>Exam</th><th>Scores</th><th>Band</th><th>Remark</th><th></th>
    </tr></thead>
    <tbody>
      @foreach($marks as $m)
      <tr>
        <td>{{ optional($m->student)->full_name }}</td>
        <td>{{ optional($m->subject)->name }}</td>
        <td>{{ optional($m->exam)->name }}</td>
        <td>
          O: {{ $m->opener_score ?? '-' }} | M: {{ $m->midterm_score ?? '-' }} | E: {{ $m->endterm_score ?? '-' }}
        </td>
        <td>{{ $m->grade_label }}</td>
        <td>{{ $m->subject_remark }}</td>
        <td><a class="btn btn-outline-secondary btn-sm" href="{{ route('academics.exam-marks.edit',$m) }}">Edit</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $marks->links() }}
</div>
@endsection
