@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">CBC Strands</h1>
        <a href="{{ route('academics.cbc-strands.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Strand
        </a>
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
                    <select name="learning_area" class="form-select">
                        <option value="">All Learning Areas</option>
                        @foreach($learningAreas as $area)
                            <option value="{{ $area }}" {{ request('learning_area') == $area ? 'selected' : '' }}>
                                {{ $area }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
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
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Strands Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Learning Area</th>
                            <th>Level</th>
                            <th>Substrands</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($strands as $strand)
                        <tr>
                            <td><strong>{{ $strand->code }}</strong></td>
                            <td>{{ $strand->name }}</td>
                            <td><span class="badge bg-info">{{ $strand->learning_area }}</span></td>
                            <td><span class="badge bg-secondary">{{ $strand->level }}</span></td>
                            <td>{{ $strand->substrands_count }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.cbc-strands.show', $strand) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('academics.cbc-strands.edit', $strand) }}" class="btn btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No strands found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $strands->links() }}
        </div>
    </div>
</div>
@endsection

