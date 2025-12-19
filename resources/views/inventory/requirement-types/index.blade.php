@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Requirement Types</div>
                <h1>Requirement Types</h1>
                <p>Define stationery, food items, or assets students must provide.</p>
            </div>
        </div>

        @include('partials.alerts')

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Create Type</h5>
                        <form method="POST" action="{{ route('inventory.requirement-types.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="E.g. Exercise Book, Rice" value="{{ old('name') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" placeholder="Stationery, Food, Assets" value="{{ old('category') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" rows="3" class="form-control" placeholder="Any details or specifications">{{ old('description') }}</textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="typeActive" value="1" @checked(old('is_active', true))>
                                    <label class="form-check-label" for="typeActive">Active</label>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button class="btn btn-settings-primary" type="submit">
                                    <i class="bi bi-plus-circle"></i> Add Type
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Existing Types</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-modern mb-0 align-middle">
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
                                            <td><span class="pill-badge">{{ $type->is_active ? 'Active' : 'Archived' }}</span></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-ghost-strong" type="button" data-bs-toggle="collapse" data-bs-target="#editType{{ $type->id }}">
                                                    Edit
                                                </button>
                                                <form action="{{ route('inventory.requirement-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this type? Templates linked to it will also be removed.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-ghost-strong text-danger">Delete</button>
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
                                                            <button class="btn btn-sm btn-settings-primary">Save</button>
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
</div>
@endsection

