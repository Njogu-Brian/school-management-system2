@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Homework Diary</h1>
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
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Homework</label>
                    <select name="homework_id" class="form-select">
                        <option value="">All Homework</option>
                        @foreach($homeworks as $hw)
                            <option value="{{ $hw->id }}" {{ request('homework_id') == $hw->id ? 'selected' : '' }}>
                                {{ $hw->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Submitted</option>
                        <option value="marked" {{ request('status') == 'marked' ? 'selected' : '' }}>Marked</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </form>
        </div>
    </div>

    <!-- Homework Diary Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Homework</th>
                            <th>Subject</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($homeworkDiary as $entry)
                        <tr>
                            <td>{{ $entry->student->first_name ?? '' }} {{ $entry->student->last_name ?? '' }}</td>
                            <td>{{ $entry->homework->title ?? '' }}</td>
                            <td>{{ $entry->homework->subject->name ?? '' }}</td>
                            <td>{{ $entry->homework->due_date ? $entry->homework->due_date->format('d M Y') : 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $entry->status == 'marked' ? 'success' : ($entry->status == 'submitted' ? 'info' : 'warning') }}">
                                    {{ ucfirst($entry->status) }}
                                </span>
                            </td>
                            <td>
                                @if($entry->score !== null && $entry->max_score !== null && $entry->max_score > 0)
                                    {{ $entry->score }}/{{ $entry->max_score }} ({{ number_format($entry->percentage, 1) }}%)
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('academics.homework-diary.show', $entry) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No homework diary entries found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $homeworkDiary->links() }}
        </div>
    </div>
</div>
@endsection

