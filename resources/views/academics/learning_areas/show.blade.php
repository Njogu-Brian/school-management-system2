@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $learning_area->name }}</h1>
        <div>
            @can('learning_areas.edit')
            <a href="{{ route('academics.learning-areas.edit', $learning_area) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Learning Area Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Code:</dt>
                        <dd class="col-sm-9"><strong>{{ $learning_area->code }}</strong></dd>

                        <dt class="col-sm-3">Name:</dt>
                        <dd class="col-sm-9">{{ $learning_area->name }}</dd>

                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9">{{ $learning_area->description ?? '-' }}</dd>

                        <dt class="col-sm-3">Level Category:</dt>
                        <dd class="col-sm-9">
                            @if($learning_area->level_category)
                                <span class="badge bg-info">{{ $learning_area->level_category }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Levels:</dt>
                        <dd class="col-sm-9">
                            @if($learning_area->levels && count($learning_area->levels) > 0)
                                @foreach($learning_area->levels as $level)
                                    <span class="badge bg-secondary me-1">{{ $level }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Type:</dt>
                        <dd class="col-sm-9">
                            @if($learning_area->is_core)
                                <span class="badge bg-primary">Core</span>
                            @else
                                <span class="badge bg-secondary">Optional</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            @if($learning_area->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Strands ({{ $strands->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($strands->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th>Level</th>
                                        <th>Substrands</th>
                                        <th>Competencies</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($strands as $strand)
                                    <tr>
                                        <td><strong>{{ $strand->code }}</strong></td>
                                        <td>{{ $strand->name }}</td>
                                        <td><span class="badge bg-secondary">{{ $strand->level }}</span></td>
                                        <td>{{ $strand->substrands_count ?? 0 }}</td>
                                        <td>{{ $strand->competencies_count ?? 0 }}</td>
                                        <td>
                                            <a href="{{ route('academics.cbc-strands.show', $strand) }}" 
                                               class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No strands found for this learning area.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @can('cbc_strands.create')
                    <a href="{{ route('academics.cbc-strands.create') }}?learning_area={{ $learning_area->id }}" 
                       class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Add Strand
                    </a>
                    @endcan
                    @can('competencies.create')
                    <a href="{{ route('academics.competencies.create') }}" 
                       class="btn btn-info w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Add Competency
                    </a>
                    @endcan
                    <a href="{{ route('academics.cbc-strands.index') }}?learning_area={{ $learning_area->name }}" 
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-list"></i> View All Strands
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

