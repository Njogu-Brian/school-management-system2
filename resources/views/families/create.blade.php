@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('families.index') }}">Families</a></li>
      <li class="breadcrumb-item active">Create</li>
    </ol>
  </nav>

  @include('students.partials.alerts')

  <form class="card" action="{{ route('families.store') }}" method="POST">
    @csrf
    <div class="card-header">New Family</div>
    <div class="card-body vstack gap-3">
      <div>
        <label class="form-label">Guardian Name</label>
        <input type="text" name="guardian_name" class="form-control" required>
      </div>
      <div>
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div>
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control">
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2">
      <a href="{{ route('families.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <button class="btn btn-success"><i class="bi bi-check-lg"></i> Create</button>
    </div>
  </form>
</div>
@endsection
