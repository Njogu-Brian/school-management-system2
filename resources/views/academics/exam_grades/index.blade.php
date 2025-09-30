@extends('layouts.app')
@section('content')
<div class="container">
  <h1 class="mb-3">Exam Grades</h1>
  <a href="{{ route('academics.exam-grades.create') }}" class="btn btn-primary mb-3">Add Grade Band</a>
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <table class="table table-sm table-striped">
    <thead><tr>
      <th>Exam Type</th><th>Band</th><th>From</th><th>To</th><th>Point</th><th>Description</th><th></th>
    </tr></thead>
    <tbody>
      @foreach($grades as $g)
        <tr>
          <td>{{ $g->exam_type }}</td>
          <td><strong>{{ $g->grade_name }}</strong></td>
          <td>{{ $g->percent_from }}%</td>
          <td>{{ $g->percent_upto }}%</td>
          <td>{{ $g->grade_point }}</td>
          <td>{{ $g->description }}</td>
          <td class="text-nowrap">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('academics.exam-grades.edit',$g) }}">Edit</a>
            <form action="{{ route('academics.exam-grades.destroy',$g) }}" method="POST" class="d-inline">
              @csrf @method('DELETE')
              <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete band?')">Delete</button>
            </form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
  {{ $grades->links() }}
</div>
@endsection
