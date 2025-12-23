@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Fee Payment Reminders',
        'icon' => 'bi bi-bell',
        'subtitle' => 'Manage and send payment reminders to students',
        'actions' => '
            <a href="' . route('finance.fee-reminders.create') . '" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-plus-circle"></i> Create Reminder
            </a>
            <form action="' . route('finance.fee-reminders.automated') . '" method="POST" class="d-inline">
                ' . csrf_field() . '
                <button type="submit" class="btn btn-finance btn-finance-success">
                    <i class="bi bi-send"></i> Send Automated Reminders
                </button>
            </form>
        '
    ])

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

    <div class="finance-filter-card finance-animate">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Status</label>
                <select name="status" class="finance-form-select">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Student</label>
                <select name="student_id" class="finance-form-select">
                    <option value="">All Students</option>
                    @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                        <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->first_name }} {{ $student->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
                <thead>
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
                                <td colspan="8">
                                    <div class="finance-empty-state">
                                        <div class="finance-empty-state-icon">
                                            <i class="bi bi-bell"></i>
                                        </div>
                                        <h4>No reminders found</h4>
                                        <p class="text-muted mb-3">Create your first payment reminder to get started</p>
                                        <a href="{{ route('finance.fee-reminders.create') }}" class="btn btn-finance btn-finance-primary">
                                            <i class="bi bi-plus-circle"></i> Create Reminder
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        @if($reminders->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $reminders->links() }}
        </div>
        @endif
    </div>
  </div>
</div>
@endsection

