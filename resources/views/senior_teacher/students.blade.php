@extends('layouts.app')

@push('styles')
    @include('senior_teacher.partials.styles')
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-people me-2"></i>Students</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Students</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('senior_teacher.students.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Classroom</label>
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
                        <label class="form-label">Stream</label>
                        <select name="stream_id" class="form-select">
                            <option value="">All Streams</option>
                            @foreach($streams as $stream)
                                <option value="{{ $stream->id }}" {{ request('stream_id') == $stream->id ? 'selected' : '' }}>
                                    {{ $stream->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="Graduated" {{ request('status') === 'Graduated' ? 'selected' : '' }}>Graduated</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Name or Admission No." value="{{ request('search') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('senior_teacher.students.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Students Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Students List</h5>
            <span class="badge bg-primary">{{ $students->total() }} Students</span>
        </div>
        <div class="card-body">
            @if($students->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3 text-muted">No Students Found</h4>
                    <p class="text-muted">No students match your filter criteria.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Admission No.</th>
                                <th>Name</th>
                                <th>Class/Stream</th>
                                <th>Parent/Guardian</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td><strong>{{ $student->admission_number }}</strong></td>
                                    <td>
                                        <div>
                                            <strong>{{ $student->full_name }}</strong>
                                            @if($student->email)
                                                <br><small class="text-muted">{{ $student->email }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        {{ $student->classroom->name ?? 'N/A' }}
                                        @if($student->stream)
                                            <br><small class="text-muted">{{ $student->stream->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->parent)
                                            {{ $student->parent->primary_contact_name ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $student->parent->primary_contact_phone ?? 'N/A' }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $student->status === 'Active' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $student->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('senior_teacher.students.show', $student->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

