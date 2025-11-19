@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Fee Payment Reminders</h1>
        <div class="btn-group">
            <a href="{{ route('finance.fee-reminders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Reminder
            </a>
            <form action="{{ route('finance.fee-reminders.automated') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-send"></i> Send Automated Reminders
                </button>
            </form>
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

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Outstanding Amount</th>
                            <th>Due Date</th>
                            <th>Days Before Due</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reminders as $reminder)
                            <tr>
                                <td>{{ $reminder->student->first_name }} {{ $reminder->student->last_name }}</td>
                                <td>KES {{ number_format($reminder->outstanding_amount, 2) }}</td>
                                <td>{{ $reminder->due_date->format('M d, Y') }}</td>
                                <td>{{ $reminder->days_before_due }} days</td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($reminder->channel) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $reminder->status == 'sent' ? 'success' : ($reminder->status == 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($reminder->status) }}
                                    </span>
                                </td>
                                <td>{{ $reminder->sent_at ? $reminder->sent_at->format('M d, Y H:i') : '-' }}</td>
                                <td>
                                    @if($reminder->status == 'pending')
                                        <form action="{{ route('finance.fee-reminders.send', $reminder) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Send</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No reminders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $reminders->links() }}
        </div>
    </div>
</div>
@endsection

