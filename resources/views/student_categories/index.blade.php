@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Student Categories</h1>
        <p class="text-muted mb-0">Manage groupings such as staff children, boarding, or day scholars.</p>
      </div>
      <a href="{{ route('student-categories.create') }}" class="btn btn-settings-primary">
        <i class="bi bi-plus-circle"></i> Add Category
      </a>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card">
      <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Category Name</th>
              <th>Description</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($categories as $category)
              <tr>
                <td class="fw-semibold">{{ $category->name }}</td>
                <td class="text-muted">{{ $category->description ?? '—' }}</td>
                <td class="text-end">
                  <div class="btn-group">
                    <a href="{{ route('student-categories.edit', $category->id) }}" class="btn btn-sm btn-ghost-strong">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('student-categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Delete this category?')" class="d-inline">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i> Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted py-4">No categories yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer text-muted small">
        {{ $categories->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
