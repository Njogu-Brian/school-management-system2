@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Learning Areas</h1>
        @can('learning_areas.create')
        <a href="{{ route('academics.learning-areas.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Learning Area
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Level Category</label>
                    <select name="level_category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($levelCategories as $category)
                            <option value="{{ $category }}" {{ request('level_category') == $category ? 'selected' : '' }}>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select">
                        <option value="">All Levels</option>
                        @foreach($levels as $level)
                            <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>
                                {{ $level }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="is_core" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('is_core') == '1' ? 'selected' : '' }}>Core</option>
                        <option value="0" {{ request('is_core') == '0' ? 'selected' : '' }}>Optional</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Learning Areas Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Levels</th>
                            <th>Type</th>
                            <th>Strands</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($learningAreas as $learningArea)
                        <tr>
                            <td><strong>{{ $learningArea->code }}</strong></td>
                            <td>{{ $learningArea->name }}</td>
                            <td>
                                @if($learningArea->level_category)
                                    <span class="badge bg-info">{{ $learningArea->level_category }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($learningArea->levels)
                                    @foreach(array_slice($learningArea->levels, 0, 3) as $level)
                                        <span class="badge bg-secondary">{{ $level }}</span>
                                    @endforeach
                                    @if(count($learningArea->levels) > 3)
                                        <span class="badge bg-light text-dark">+{{ count($learningArea->levels) - 3 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($learningArea->is_core)
                                    <span class="badge bg-primary">Core</span>
                                @else
                                    <span class="badge bg-secondary">Optional</span>
                                @endif
                            </td>
                            <td>{{ $learningArea->strands_count ?? 0 }}</td>
                            <td>
                                @if($learningArea->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.learning-areas.show', $learningArea) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('learning_areas.edit')
                                    <a href="{{ route('academics.learning-areas.edit', $learningArea) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('learning_areas.delete')
                                    <form action="{{ route('academics.learning-areas.destroy', $learningArea) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this learning area?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No learning areas found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $learningAreas->links() }}
        </div>
    </div>
</div>
@endsection

