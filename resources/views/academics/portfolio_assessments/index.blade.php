@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Portfolio Assessments</h1>
        <a href="{{ route('academics.portfolio-assessments.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Assessment
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
                <div class="col-md-2">
                    <select name="classroom_id" class="form-select">
                        <option value="">All Classrooms</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
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
                    <select name="portfolio_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="project" {{ request('portfolio_type') == 'project' ? 'selected' : '' }}>Project</option>
                        <option value="practical" {{ request('portfolio_type') == 'practical' ? 'selected' : '' }}>Practical</option>
                        <option value="creative" {{ request('portfolio_type') == 'creative' ? 'selected' : '' }}>Creative</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="assessed" {{ request('status') == 'assessed' ? 'selected' : '' }}>Assessed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Portfolios Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($portfolios as $portfolio)
                        <tr>
                            <td>{{ $portfolio->title }}</td>
                            <td>{{ $portfolio->student->full_name }}</td>
                            <td>{{ $portfolio->subject->name }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($portfolio->portfolio_type) }}</span></td>
                            <td>{{ $portfolio->total_score ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $portfolio->status == 'assessed' ? 'success' : ($portfolio->status == 'published' ? 'primary' : 'warning') }}">
                                    {{ ucfirst($portfolio->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.portfolio-assessments.show', $portfolio) }}" class="btn btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('academics.portfolio-assessments.edit', $portfolio) }}" class="btn btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No portfolio assessments found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $portfolios->links() }}
        </div>
    </div>
</div>
@endsection


