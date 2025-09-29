@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Import Exams</h1>

  <div class="card">
    <div class="card-body">
      <p class="mb-3">
        Download the <a href="{{ route('academics.exams.template') }}">template</a>,
        fill it, then upload the file (.xlsx, .xls, .csv).
      </p>

      <form action="{{ route('academics.exams.import.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="form-label">File</label>
          <input type="file" name="file" class="form-control" required>
        </div>
        <button class="btn btn-primary">Import</button>
        <a href="{{ route('academics.exams.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </form>
    </div>
  </div>
</div>
@endsection
