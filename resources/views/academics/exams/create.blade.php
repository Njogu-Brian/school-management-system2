@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">New Exam</h3>
    <a href="{{ route('academics.exams.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  @includeIf('partials.alerts')

  <form method="post" action="{{ route('academics.exams.store') }}" class="card shadow-sm">
    @csrf
    <div class="card-body">
      @include('academics.exams.partials.form', ['mode' => 'create'])
    </div>
    <div class="card-footer text-end">
      <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Create Exam</button>
    </div>
  </form>
</div>
@endsection
