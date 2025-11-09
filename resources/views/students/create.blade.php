@extends('layouts.app')

@section('content')
<div class="container">
  @include('students.partials.breadcrumbs', ['trail' => ['Create' => null]])

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Student Admission</h1>
    <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  @include('students.partials.alerts')

  <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="card">
    @include('students.partials.form', [
      'mode' => 'create',
      // controller should pass these:
      // 'classrooms'=>$classrooms, 'streams'=>$streams, 'categories'=>$categories, 'transportRoutes'=>$routes
    ])
  </form>
</div>
@endsection
