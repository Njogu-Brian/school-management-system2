@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $cbc_strand->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.cbc-strands.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.cbc-strands.edit', $cbc_strand) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Strand Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Code:</th>
                            <td><strong>{{ $cbc_strand->code }}</strong></td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $cbc_strand->name }}</td>
                        </tr>
                        <tr>
                            <th>Learning Area:</th>
                            <td><span class="badge bg-info">{{ $cbc_strand->learning_area }}</span></td>
                        </tr>
                        <tr>
                            <th>Level:</th>
                            <td><span class="badge bg-secondary">{{ $cbc_strand->level }}</span></td>
                        </tr>
                        @if($cbc_strand->description)
                        <tr>
                            <th>Description:</th>
                            <td>{{ $cbc_strand->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($cbc_strand->substrands->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Substrands ({{ $cbc_strand->substrands->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Learning Outcomes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cbc_strand->substrands as $substrand)
                                <tr>
                                    <td><strong>{{ $substrand->code }}</strong></td>
                                    <td>{{ $substrand->name }}</td>
                                    <td>
                                        @if($substrand->learning_outcomes)
                                            @if(is_array($substrand->learning_outcomes))
                                                <ul class="mb-0">
                                                    @foreach($substrand->learning_outcomes as $outcome)
                                                        <li>{{ $outcome }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {{ $substrand->learning_outcomes }}
                                            @endif
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
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
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $cbc_strand->is_active ? 'success' : 'secondary' }}">
                            {{ $cbc_strand->is_active ? 'Active' : 'Inactive' }}
                        </span><br>
                        <strong>Display Order:</strong> {{ $cbc_strand->display_order }}<br>
                        <strong>Created:</strong> {{ $cbc_strand->created_at->format('d M Y') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


