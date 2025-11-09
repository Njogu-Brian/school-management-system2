@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item active">Families</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h1 class="h4 mb-0">Families (Siblings)</h1>
    @if(Route::has('families.create'))
      <a href="{{ route('families.create') }}" class="btn btn-success"><i class="bi bi-plus"></i> New Family</a>
    @endif
  </div>

  @include('students.partials.alerts')

  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2">
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" name="q" value="{{ $q }}" placeholder="Guardian name, phone, email">
          </div>
        </div>
        <div class="col-md-2">
          <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Guardian</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Members</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($families as $fam)
            <tr>
              <td>{{ $fam->id }}</td>
              <td>{{ $fam->guardian_name }}</td>
              <td>{{ $fam->phone ?? '—' }}</td>
              <td>{{ $fam->email ?? '—' }}</td>
              <td><span class="badge bg-primary">{{ $fam->students_count }}</span></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="{{ route('families.manage', $fam) }}">
                  <i class="bi bi-people"></i> Manage
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No families found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $families->links() }}
    </div>
  </div>
</div>
@endsection
