@extends('layouts.app')

@section('content')
<div class="finance-page schedule-page">
  <div class="finance-shell schedule-shell">
    @include('finance.partials.header', [
        'title' => 'Fee Payment Reminders',
        'icon' => 'bi bi-bell',
        'subtitle' => 'Send or schedule fee reminders to parents. Balances are checked fresh at send time.',
        'actions' => '
            <a href="' . route('finance.fee-reminders.schedule.create') . '" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-send-plus"></i> Send or Schedule
            </a>
            <a href="' . route('finance.fee-reminders.index', ['tab' => 'scheduled']) . '" class="btn btn-finance ' . (($tab ?? 'sent') === 'scheduled' ? 'btn-finance-primary' : 'btn-finance-outline') . '">
                <i class="bi bi-calendar-check"></i> Scheduled
            </a>
            <a href="' . route('finance.fee-reminders.index', ['tab' => 'sent']) . '" class="btn btn-finance ' . (($tab ?? 'sent') === 'sent' ? 'btn-finance-primary' : 'btn-finance-outline') . '">
                <i class="bi bi-bell"></i> Sent
            </a>
            <form action="' . route('finance.fee-reminders.automated') . '" method="POST" class="d-inline">
                ' . csrf_field() . '
                <button type="submit" class="btn btn-finance btn-finance-success">
                    <i class="bi bi-lightning"></i> Send Due Reminders
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

    @if(($tab ?? 'sent') === 'sent')
    {{-- Sent Reminders Tab --}}
    <div class="finance-filter-card finance-animate">
        <form method="GET" class="row g-3">
            <input type="hidden" name="tab" value="sent">
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
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'student_id',
                    'displayInputId' => 'feeReminderFilterStudent',
                    'resultsId' => 'feeReminderFilterResults',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => request('student_id') ? optional(\App\Models\Student::find(request('student_id')))->search_display : ''
                ])
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
                            <td>{{ $reminder->student->full_name }}</td>
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
                                    <a href="{{ route('finance.fee-reminders.schedule.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-send-plus"></i> Send or Schedule
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
            {{ $reminders->withQueryString()->links() }}
        </div>
        @endif
    </div>
    @else
    {{-- Scheduled Tab --}}
    <div class="schedule-card schedule-card-glow schedule-filter-card finance-animate">
        <div class="schedule-card-body">
            <form method="GET" class="schedule-filter-form">
                <input type="hidden" name="tab" value="scheduled">
                <div class="schedule-filter-row">
                    <div class="schedule-field">
                        <label class="schedule-label">Status</label>
                        <select name="status" class="schedule-select schedule-select-sm">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active (recurring)</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="schedule-btn schedule-btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="schedule-card schedule-card-glow schedule-table-card finance-table-wrapper finance-animate">
        <div class="schedule-table-wrapper">
            <table class="schedule-table finance-table">
                <thead>
                    <tr>
                        <th>Schedule</th>
                        <th>Target</th>
                        <th>Filter</th>
                        <th>Channels</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scheduled as $item)
                        <tr>
                            <td>
                                @if($item->isRecurring())
                                    <span class="schedule-recurrence-badge">{{ $item->recurrence_description }}</span>
                                    <br><small class="schedule-muted">Next: {{ $item->recurrence_next_at?->format('M d, H:i') ?? '-' }}</small>
                                @else
                                    {{ $item->send_at->format('M d, Y H:i') }}
                                @endif
                            </td>
                            <td>
                                @switch($item->target)
                                    @case('one_parent')
                                        One parent
                                        @if($item->student)
                                            <br><small class="schedule-muted">{{ $item->student->full_name }}</small>
                                        @endif
                                        @break
                                    @case('specific_students')
                                        Specific students
                                        @break
                                    @case('class')
                                        Class(es)
                                        @break
                                    @case('all')
                                        All parents
                                        @break
                                    @default
                                        {{ $item->target }}
                                @endswitch
                            </td>
                            <td>
                                @switch($item->filter_type)
                                    @case('outstanding_fees')
                                        Outstanding fees
                                        @if($item->balance_min)
                                            <br><small class="schedule-muted">≥ KES {{ number_format($item->balance_min, 2) }}</small>
                                        @endif
                                        @break
                                    @case('upcoming_invoices')
                                        Upcoming invoices
                                        @break
                                    @case('swimming_balance')
                                        Swimming balance
                                        @if($item->balance_min)
                                            <br><small class="schedule-muted">≥ KES {{ number_format($item->balance_min, 2) }}</small>
                                        @endif
                                        @break
                                    @default
                                        All
                                @endswitch
                            </td>
                            <td>
                                @foreach($item->channels ?? [] as $ch)
                                    <span class="schedule-channel-badge">{{ ucfirst($ch) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="schedule-status-badge schedule-status-{{ $item->status }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->createdBy->name ?? '-' }}</td>
                            <td>
                                @if(in_array($item->status, ['pending', 'active']))
                                    <form action="{{ route('finance.fee-reminders.schedule.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this scheduled communication?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="schedule-btn schedule-btn-icon schedule-btn-sm schedule-btn-danger">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="schedule-empty">
                                    <div class="schedule-empty-icon"><i class="bi bi-calendar-x"></i></div>
                                    <h3>No scheduled communications</h3>
                                    <p class="schedule-muted">Schedule one-time or recurring fee reminders to parents</p>
                                    <a href="{{ route('finance.fee-reminders.schedule.create') }}" class="schedule-btn schedule-btn-primary">
                                        <i class="bi bi-plus-circle"></i> Schedule Communication
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($scheduled->hasPages())
        <div class="schedule-pagination">
            {{ $scheduled->withQueryString()->links() }}
        </div>
        @endif
    </div>
    @endif
  </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
<style>
.schedule-filter-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.schedule-filter-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.schedule-select-sm { max-width: 180px; }
.schedule-table { width: 100%; border-collapse: collapse; }
.schedule-table th, .schedule-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--schedule-border, var(--fin-border, #e5e7eb)); }
.schedule-table th { font-weight: 600; color: var(--schedule-muted, var(--fin-muted, #6b7280)); font-size: 0.85rem; text-transform: uppercase; }
.schedule-table tbody tr:hover { background: var(--schedule-surface-elevated, rgba(0,0,0,0.02)); }
.schedule-recurrence-badge { font-size: 0.85rem; color: var(--schedule-accent, var(--fin-accent)); }
.schedule-channel-badge { display: inline-block; padding: 0.2rem 0.5rem; background: color-mix(in srgb, var(--brand-primary, #0f766e) 20%, transparent); border-radius: 6px; font-size: 0.75rem; margin-right: 0.25rem; }
.schedule-status-badge { padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.schedule-status-pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
.schedule-status-active { background: rgba(34,211,238,0.2); color: #22d3ee; }
.schedule-status-sent { background: rgba(16,185,129,0.2); color: #10b981; }
.schedule-status-completed { background: color-mix(in srgb, var(--brand-primary) 20%, transparent); color: var(--brand-primary); }
.schedule-status-cancelled { background: rgba(139,139,167,0.2); color: var(--schedule-muted, #6b7280); }
.schedule-btn-danger { color: #ef4444 !important; border-color: rgba(239,68,68,0.4) !important; }
.schedule-btn-danger:hover { background: rgba(239,68,68,0.1) !important; }
.schedule-empty { text-align: center; padding: 3rem 2rem; }
.schedule-empty-icon { font-size: 3rem; color: var(--schedule-muted, #6b7280); margin-bottom: 1rem; }
.schedule-empty h3 { margin-bottom: 0.5rem; }
.schedule-pagination { padding: 1rem; border-top: 1px solid var(--schedule-border, #e5e7eb); }
</style>
@endpush
@endsection
