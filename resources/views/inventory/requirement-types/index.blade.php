@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Requirement Types</h1>
            <p class="text-muted mb-0">Define stationery, food items or assets that students must provide every term.</p>
        </div>
    </div>

    @include('partials.alerts')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Create Type</h2>
                    <form method="POST" action="{{ route('inventory.requirement-types.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="E.g. Exercise Book, Rice" value="{{ old('name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="Stationery, Food, Assets" value="{{ old('category') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Any details or specifications">{{ old('description') }}</textarea>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-plus-circle"></i> Add Type
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">Existing Types</h2>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($types as $type)
                                    <tr>
                                        <td>{{ $type->name }}</td>
                                        <td>{{ $type->category ?? '—' }}</td>
                                        <td class="text-muted">{{ $type->description ?? '—' }}</td>
                                        <td>
                                            @if($type->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Archived</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editType{{ $type->id }}">
                                                Edit
                                            </button>
                                            <form action="{{ route('inventory.requirement-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this type? Templates linked to it will also be removed.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="editType{{ $type->id }}">
                                        <td colspan="5">
                                            <form method="POST" action="{{ route('inventory.requirement-types.update', $type) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-3">
                                                        <label class="form-label small text-muted">Name</label>
                                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $type->name }}" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small text-muted">Category</label>
                                                        <input type="text" name="category" class="form-control form-control-sm" value="{{ $type->category }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small text-muted">Description</label>
                                                        <input type="text" name="description" class="form-control form-control-sm" value="{{ $type->description }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-check mt-4 pt-1">
                                                            <input class="form-check-input" type="checkbox" value="1" name="is_active" id="typeActive{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label small" for="typeActive{{ $type->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 text-end">
                                                        <button class="btn btn-sm btn-primary">Save</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No requirement types defined yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

