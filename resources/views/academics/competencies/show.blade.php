@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $competency->name }}</h1>
        <div>
            @can('competencies.edit')
            <a href="{{ route('academics.competencies.edit', $competency) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('academics.competencies.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Competency Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Code:</dt>
                        <dd class="col-sm-9"><strong>{{ $competency->code }}</strong></dd>

                        <dt class="col-sm-3">Name:</dt>
                        <dd class="col-sm-9">{{ $competency->name }}</dd>

                        <dt class="col-sm-3">Description:</dt>
                        <dd class="col-sm-9">{{ $competency->description ?? '-' }}</dd>

                        <dt class="col-sm-3">Learning Area:</dt>
                        <dd class="col-sm-9">
                            @if($competency->substrand && $competency->substrand->strand && $competency->substrand->strand->learningArea)
                                <span class="badge bg-info">{{ $competency->substrand->strand->learningArea->name }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Strand:</dt>
                        <dd class="col-sm-9">{{ $competency->substrand->strand->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Substrand:</dt>
                        <dd class="col-sm-9">{{ $competency->substrand->name ?? '-' }}</dd>

                        <dt class="col-sm-3">Competency Level:</dt>
                        <dd class="col-sm-9">
                            @if($competency->competency_level)
                                <span class="badge bg-secondary">{{ $competency->competency_level }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            @if($competency->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            @if($competency->indicators && count($competency->indicators) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Indicators</h5>
                </div>
                <div class="card-body">
                    <ul>
                        @foreach($competency->indicators as $indicator)
                            <li>{{ is_array($indicator) ? ($indicator['text'] ?? '') : $indicator }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if($competency->assessment_criteria && count($competency->assessment_criteria) > 0)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Assessment Criteria</h5>
                </div>
                <div class="card-body">
                    <ul>
                        @foreach($competency->assessment_criteria as $criterion)
                            <li>{{ is_array($criterion) ? ($criterion['text'] ?? '') : $criterion }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @if($competency->substrand)
                    <a href="{{ route('academics.cbc-substrands.show', $competency->substrand) }}" 
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-eye"></i> View Substrand
                    </a>
                    @endif
                    @if($competency->substrand && $competency->substrand->strand)
                    <a href="{{ route('academics.cbc-strands.show', $competency->substrand->strand) }}" 
                       class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-eye"></i> View Strand
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

