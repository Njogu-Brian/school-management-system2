@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Student Promotions</h2>
            <small class="text-muted">Promote students to next class based on class mapping</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Class Mapping & Promotions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Next Class</th>
                            <th>Students</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classrooms as $classroom)
                            <tr>
                                <td class="fw-semibold">{{ $classroom->name }}</td>
                                <td>
                                    @if($classroom->is_beginner)
                                        <span class="badge bg-info">Beginner</span>
                                    @endif
                                    @if($classroom->is_alumni)
                                        <span class="badge bg-warning">Alumni</span>
                                    @endif
                                    @if(!$classroom->is_beginner && !$classroom->is_alumni)
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($classroom->is_alumni)
                                        <span class="text-muted">
                                            <i class="bi bi-trophy"></i> Graduation → Alumni
                                        </span>
                                    @elseif($classroom->nextClass)
                                        <span class="text-success">
                                            <i class="bi bi-arrow-right"></i> {{ $classroom->nextClass->name }}
                                        </span>
                                    @else
                                        <span class="text-danger">
                                            <i class="bi bi-exclamation-triangle"></i> Not Mapped
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $classroom->students->count() }} students</span>
                                </td>
                                <td class="text-end">
                                    @if($classroom->students->count() > 0)
                                        <a href="{{ route('academics.promotions.show', $classroom) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-arrow-up-circle"></i> Promote Students
                                        </a>
                                    @else
                                        <span class="text-muted">No students</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No classrooms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

