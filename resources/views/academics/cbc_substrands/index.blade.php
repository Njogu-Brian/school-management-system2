@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">CBC Substrands</h1>
        @can('cbc_strands.manage')
        <a href="{{ route('academics.cbc-substrands.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Substrand
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
                <div class="col-md-4">
                    <label class="form-label">Strand</label>
                    <select name="strand_id" class="form-select">
                        <option value="">All Strands</option>
                        @foreach($strands as $strand)
                            <option value="{{ $strand->id }}" {{ request('strand_id') == $strand->id ? 'selected' : '' }}>
                                {{ $strand->code }} - {{ $strand->name }} ({{ $strand->level }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="{{ request('search') }}" placeholder="Search by name or code">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Substrands Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Strand</th>
                            <th>Learning Area</th>
                            <th>Level</th>
                            <th>Suggested Lessons</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($substrands as $substrand)
                        <tr>
                            <td><strong>{{ $substrand->code }}</strong></td>
                            <td>{{ $substrand->name }}</td>
                            <td>
                                <a href="{{ route('academics.cbc-strands.show', $substrand->strand_id) }}" 
                                   class="text-decoration-none">
                                    {{ $substrand->strand->name ?? 'N/A' }}
                                </a>
                            </td>
                            <td>
                                @if($substrand->strand)
                                    <span class="badge bg-info">{{ $substrand->strand->learning_area ?? 'N/A' }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($substrand->strand)
                                    <span class="badge bg-secondary">{{ $substrand->strand->level ?? 'N/A' }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $substrand->suggested_lessons ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $substrand->is_active ? 'success' : 'secondary' }}">
                                    {{ $substrand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.cbc-substrands.show', $substrand) }}" 
                                       class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('cbc_strands.manage')
                                    <a href="{{ route('academics.cbc-substrands.edit', $substrand) }}" 
                                       class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No substrands found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $substrands->links() }}
        </div>
    </div>
</div>
@endsection

