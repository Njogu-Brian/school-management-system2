@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Competencies</h1>
        @can('competencies.create')
        <a href="{{ route('academics.competencies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Competency
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Learning Area</label>
                    <select name="learning_area_id" class="form-select">
                        <option value="">All Learning Areas</option>
                        @foreach($learningAreas as $area)
                            <option value="{{ $area->id }}" {{ request('learning_area_id') == $area->id ? 'selected' : '' }}>
                                {{ $area->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Strand</label>
                    <select name="strand_id" class="form-select">
                        <option value="">All Strands</option>
                        @foreach($strands as $strand)
                            <option value="{{ $strand->id }}" {{ request('strand_id') == $strand->id ? 'selected' : '' }}>
                                {{ $strand->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Substrand</label>
                    <select name="substrand_id" class="form-select">
                        <option value="">All Substrands</option>
                        @foreach($substrands as $substrand)
                            <option value="{{ $substrand->id }}" {{ request('substrand_id') == $substrand->id ? 'selected' : '' }}>
                                {{ $substrand->name }} ({{ $substrand->strand->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Level</label>
                    <select name="competency_level" class="form-select">
                        <option value="">All Levels</option>
                        @foreach($competencyLevels as $level)
                            <option value="{{ $level }}" {{ request('competency_level') == $level ? 'selected' : '' }}>
                                {{ $level }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
            <div class="row mt-3">
                <div class="col-md-12">
                    <input type="text" name="search" class="form-control" placeholder="Search by code, name, or description..." value="{{ request('search') }}">
                </div>
            </div>
        </div>
    </div>

    <!-- Competencies Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Learning Area</th>
                            <th>Strand</th>
                            <th>Substrand</th>
                            <th>Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($competencies as $competency)
                        <tr>
                            <td><strong>{{ $competency->code }}</strong></td>
                            <td>{{ $competency->name }}</td>
                            <td>
                                @if($competency->substrand && $competency->substrand->strand && $competency->substrand->strand->learningArea)
                                    <span class="badge bg-info">{{ $competency->substrand->strand->learningArea->name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $competency->substrand->strand->name ?? '-' }}</td>
                            <td>{{ $competency->substrand->name ?? '-' }}</td>
                            <td>
                                @if($competency->competency_level)
                                    <span class="badge bg-secondary">{{ $competency->competency_level }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($competency->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.competencies.show', $competency) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('competencies.edit')
                                    <a href="{{ route('academics.competencies.edit', $competency) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('competencies.delete')
                                    <form action="{{ route('academics.competencies.destroy', $competency) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
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
                            <td colspan="8" class="text-center text-muted py-4">No competencies found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $competencies->links() }}
        </div>
    </div>
</div>
@endsection

