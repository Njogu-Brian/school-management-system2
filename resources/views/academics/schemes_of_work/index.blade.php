@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Schemes of Work</h1>
        <div>
            @can('schemes_of_work.create')
            <a href="{{ route('academics.schemes-of-work.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Scheme
            </a>
            @endcan
        </div>
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
                    <select name="classroom_id" class="form-select">
                        <option value="">All Classrooms</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="subject_id" class="form-select">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schemes Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Classroom</th>
                            <th>Year</th>
                            <th>Term</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schemes as $scheme)
                        <tr>
                            <td>{{ $scheme->title }}</td>
                            <td>{{ $scheme->subject->name }}</td>
                            <td>{{ $scheme->classroom->name }}</td>
                            <td>{{ $scheme->academicYear->year }}</td>
                            <td>{{ $scheme->term->name }}</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $scheme->progress_percentage }}%">
                                        {{ $scheme->progress_percentage }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $scheme->status == 'active' ? 'success' : ($scheme->status == 'completed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($scheme->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.schemes-of-work.show', $scheme) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('schemes_of_work.edit')
                                    <a href="{{ route('academics.schemes-of-work.edit', $scheme) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('schemes_of_work.export_pdf')
                                    <a href="{{ route('academics.schemes-of-work.export-pdf', $scheme) }}" class="btn btn-outline-danger" title="Export PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    @endcan
                                    @can('schemes_of_work.export_excel')
                                    <a href="{{ route('academics.schemes-of-work.export-excel', $scheme) }}" class="btn btn-outline-success" title="Export Excel">
                                        <i class="bi bi-file-excel"></i>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No schemes of work found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $schemes->links() }}
        </div>
    </div>
</div>
@endsection

