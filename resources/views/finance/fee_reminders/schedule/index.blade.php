@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Scheduled Fee Communications',
        'icon' => 'bi bi-calendar-check',
        'subtitle' => 'View and manage scheduled fee communications to parents',
        'actions' => '
            <a href="' . route('finance.fee-reminders.schedule.create') . '" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-plus-circle"></i> Schedule Custom Communication
            </a>
            <a href="' . route('finance.fee-reminders.index') . '" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-bell"></i> Fee Reminders
            </a>
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
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary">Filter</button>
            </div>
        </form>
    </div>

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
                <thead>
                    <tr>
                        <th>Send At</th>
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
                            <td>{{ $item->send_at->format('M d, Y H:i') }}</td>
                            <td>
                                @switch($item->target)
                                    @case('one_parent')
                                        One parent
                                        @if($item->student)
                                            <br><small class="text-muted">{{ $item->student->full_name }}</small>
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
                                            <br><small>≥ KES {{ number_format($item->balance_min, 2) }}</small>
                                        @endif
                                        @break
                                    @case('upcoming_invoices')
                                        Upcoming invoices
                                        @break
                                    @case('swimming_balance')
                                        Swimming balance
                                        @if($item->balance_min)
                                            <br><small>≥ KES {{ number_format($item->balance_min, 2) }}</small>
                                        @endif
                                        @break
                                    @default
                                        All
                                @endswitch
                            </td>
                            <td>
                                @foreach($item->channels ?? [] as $ch)
                                    <span class="badge bg-secondary me-1">{{ ucfirst($ch) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-{{ $item->status == 'sent' ? 'success' : ($item->status == 'cancelled' ? 'secondary' : 'warning') }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->createdBy->name ?? '-' }}</td>
                            <td>
                                @if($item->status === 'pending')
                                    <form action="{{ route('finance.fee-reminders.schedule.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this scheduled communication?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <h4>No scheduled communications</h4>
                                    <p class="text-muted mb-3">Schedule a custom fee communication to send automatically at a future time</p>
                                    <a href="{{ route('finance.fee-reminders.schedule.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Schedule Custom Communication
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($scheduled->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $scheduled->links() }}
        </div>
        @endif
    </div>
  </div>
</div>
@endsection
