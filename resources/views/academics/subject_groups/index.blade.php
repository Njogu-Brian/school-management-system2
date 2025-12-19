@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Subject Groups</h1>
        <p class="text-muted mb-0">Group related subjects for easier assignment and reporting.</p>
      </div>
      <a href="{{ route('academics.subject_groups.create') }}" class="btn btn-settings-primary">
        <i class="bi bi-plus-circle"></i> Add Group
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">All Groups</h5>
          <p class="text-muted small mb-0">Name and description of subject groupings.</p>
        </div>
        <span class="input-chip">{{ $groups->total() ?? $groups->count() }} total</span>
      </div>
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
              @foreach($groups as $group)
              <tr>
                <td class="fw-semibold">{{ $group->name }}</td>
                <td class="text-muted">{{ $group->description }}</td>
                <td class="text-end">
                  <div class="d-flex justify-content-end gap-1 flex-wrap">
                    <a href="{{ route('academics.subject_groups.edit',$group) }}" class="btn btn-sm btn-ghost-strong" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('academics.subject_groups.destroy',$group) }}" method="POST" onsubmit="return confirm('Delete group?')">
                      @csrf @method('DELETE')
                      <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">
        {{ $groups->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
