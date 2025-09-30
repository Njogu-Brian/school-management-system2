@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Build Report Cards for a Class</h1>
  <form method="POST" action="{{ route('academics.report-cards.store') }}" class="row g-3 mt-2">@csrf
    <div class="col-md-4">
      <label class="form-label">Academic Year</label>
      <select name="academic_year_id" class="form-select">@foreach($years as $y)
        <option value="{{ $y->id }}">{{ $y->year }}</option>
      @endforeach</select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Term</label>
      <select name="term_id" class="form-select">@foreach($terms as $t)
        <option value="{{ $t->id }}">{{ $t->name }}</option>
      @endforeach</select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Classroom</label>
      <select name="classroom_id" class="form-select">@foreach($classrooms as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach</select>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Build</button>
    </div>
  </form>
</div>
@endsection
