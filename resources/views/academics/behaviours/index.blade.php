@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Behaviour</div>
        <h1 class="mb-1">Behaviour Categories</h1>
        <p class="text-muted mb-0">Define behaviour categories used in student records.</p>
      </div>
      <a href="{{ route('academics.behaviours.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus"></i> Add Category</a>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Description</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($behaviours as $behaviour)
                <tr>
                  <td class="fw-semibold">{{ $behaviour->name }}</td>
                  <td class="text-muted">{{ $behaviour->description }}</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.behaviours.edit',$behaviour) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      <form action="{{ route('academics.behaviours.destroy',$behaviour) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No behaviour categories defined.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
