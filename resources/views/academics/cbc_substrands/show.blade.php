@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $cbc_substrand->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @can('cbc_strands.manage')
            <a href="{{ route('academics.cbc-substrands.edit', $cbc_substrand) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Substrand Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Code:</th>
                            <td><strong>{{ $cbc_substrand->code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $cbc_substrand->name }}</td>
                        </tr>
                        <tr>
                            <th>Strand:</th>
                            <td>
                                <a href="{{ route('academics.cbc-strands.show', $cbc_substrand->strand_id) }}" 
                                   class="text-decoration-none">
                                    {{ $cbc_substrand->strand->name ?? 'N/A' }}
                                </a>
                                <span class="badge bg-info ms-2">{{ $cbc_substrand->strand->learning_area ?? 'N/A' }}</span>
                                <span class="badge bg-secondary ms-1">{{ $cbc_substrand->strand->level ?? 'N/A' }}</span>
                            </td>
                        </tr>
                        @if($cbc_substrand->description)
                        <tr>
                            <th>Description:</th>
                            <td>{{ $cbc_substrand->description }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Suggested Lessons:</th>
                            <td>{{ $cbc_substrand->suggested_lessons ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $cbc_substrand->is_active ? 'success' : 'secondary' }}">
                                    {{ $cbc_substrand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($cbc_substrand->learning_outcomes && count($cbc_substrand->learning_outcomes) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Learning Outcomes</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach($cbc_substrand->learning_outcomes as $outcome)
                            <li>{{ $outcome }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if($cbc_substrand->key_inquiry_questions && count($cbc_substrand->key_inquiry_questions) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Key Inquiry Questions</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach($cbc_substrand->key_inquiry_questions as $question)
                            <li>{{ $question }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if($cbc_substrand->core_competencies && count($cbc_substrand->core_competencies) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Core Competencies</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($cbc_substrand->core_competencies as $competency)
                            <span class="badge bg-primary">{{ $competency }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($cbc_substrand->values && count($cbc_substrand->values) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Values</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($cbc_substrand->values as $value)
                            <span class="badge bg-success">{{ $value }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($cbc_substrand->pclc && count($cbc_substrand->pclc) > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">PCLC (Parent, Community, Learner)</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        @foreach($cbc_substrand->pclc as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @php
                $competencies = \App\Models\Academics\Competency::where('substrand_id', $cbc_substrand->id)
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->orderBy('name')
                    ->get();
            @endphp

            @if($competencies->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Competencies ({{ $competencies->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($competencies as $competency)
                                <tr>
                                    <td><strong>{{ $competency->code }}</strong></td>
                                    <td>{{ $competency->name }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($competency->description ?? 'N/A', 50) }}</td>
                                    <td>
                                        <a href="{{ route('academics.competencies.show', $competency) }}" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $cbc_substrand->is_active ? 'success' : 'secondary' }}">
                            {{ $cbc_substrand->is_active ? 'Active' : 'Inactive' }}
                        </span><br>
                        <strong>Display Order:</strong> {{ $cbc_substrand->display_order }}<br>
                        <strong>Suggested Lessons:</strong> {{ $cbc_substrand->suggested_lessons ?? 'N/A' }}<br>
                        <strong>Created:</strong> {{ $cbc_substrand->created_at->format('d M Y') }}<br>
                        <strong>Updated:</strong> {{ $cbc_substrand->updated_at->format('d M Y') }}
                    </small>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @can('competencies.create')
                    <a href="{{ route('academics.competencies.create') }}?substrand_id={{ $cbc_substrand->id }}" 
                       class="btn btn-info w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Add Competency
                    </a>
                    @endcan
                    <a href="{{ route('academics.cbc-strands.show', $cbc_substrand->strand_id) }}" 
                       class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-arrow-left"></i> View Strand
                    </a>
                    <a href="{{ route('academics.competencies.index') }}?substrand_id={{ $cbc_substrand->id }}" 
                       class="btn btn-outline-info w-100">
                        <i class="bi bi-list"></i> View Competencies
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

