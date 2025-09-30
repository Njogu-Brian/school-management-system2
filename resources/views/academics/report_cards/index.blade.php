@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Report Cards</h1>
    <a class="btn btn-primary" href="{{ route('academics.report-cards.create') }}">Build for Class</a>
  </div>
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

  <table class="table table-sm table-striped">
    <thead><tr>
      <th>Student</th><th>Year / Term</th><th>Class</th><th>Avg</th><th>Published</th><th></th>
    </tr></thead>
    <tbody>
      @foreach($reports as $r)
      <tr>
        <td>{{ optional($r->student)->full_name }}</td>
        <td>{{ optional($r->academicYear)->year }} / {{ optional($r->term)->name }}</td>
        <td>{{ optional($r->classroom)->name }}</td>
        <td>{{ $r->summary['avg'] ?? '-' }}</td>
        <td>{{ $r->published_at ? $r->published_at->format('d M Y') : 'No' }}</td>
        <td class="text-nowrap">
          <a class="btn btn-outline-secondary btn-sm" href="{{ route('academics.report-cards.show',$r) }}">View</a>
          <a class="btn btn-outline-primary btn-sm" href="{{ route('academics.report-cards.edit',$r) }}">Edit</a>
          <form action="{{ route('academics.report-cards.publish',$r) }}" method="POST" class="d-inline">@csrf
            <button class="btn btn-outline-success btn-sm">Publish</button>
          </form>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $reports->links() }}
</div>
@endsection
