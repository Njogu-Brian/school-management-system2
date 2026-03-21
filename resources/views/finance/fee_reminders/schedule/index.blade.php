@extends('layouts.app')

@section('content')
<div class="schedule-page">
  <div class="schedule-shell">
    <div class="schedule-hero schedule-hero-index">
      <div class="schedule-hero-content">
        <h1 class="schedule-hero-title">
          <i class="bi bi-calendar-check"></i>
          Scheduled Fee Communications
        </h1>
        <p class="schedule-hero-subtitle">View and manage one-time and recurring fee communications to parents</p>
      </div>
      <div class="schedule-hero-actions">
        <a href="{{ route('finance.fee-reminders.schedule.create') }}" class="schedule-btn schedule-btn-primary">
          <i class="bi bi-plus-circle"></i> Schedule
        </a>
        <a href="{{ route('finance.fee-reminders.index') }}" class="schedule-btn schedule-btn-ghost" style="color: rgba(255,255,255,0.9); border-color: rgba(255,255,255,0.4);">
          <i class="bi bi-bell"></i> Fee Reminders
        </a>
      </div>
    </div>

    @if(session('success'))
      <div class="schedule-alert schedule-alert-success">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="schedule-alert-close" data-bs-dismiss="alert">&times;</button>
      </div>
    @endif

    @if(session('error'))
      <div class="schedule-alert schedule-alert-danger">
        <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="schedule-alert-close" data-bs-dismiss="alert">&times;</button>
      </div>
    @endif

    <div class="schedule-card schedule-card-glow schedule-filter-card">
      <div class="schedule-card-body">
        <form method="GET" class="schedule-filter-form">
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

    <div class="schedule-card schedule-card-glow schedule-table-card">
      <div class="schedule-table-wrapper">
        <table class="schedule-table">
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
          {{ $scheduled->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
<style>
.schedule-hero-index .schedule-btn-ghost:hover { color: white !important; border-color: rgba(255,255,255,0.6) !important; }
.schedule-filter-form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.schedule-filter-row { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
.schedule-select-sm { max-width: 180px; }
.schedule-table { width: 100%; border-collapse: collapse; }
.schedule-table th, .schedule-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--schedule-border); }
.schedule-table th { font-weight: 600; color: var(--schedule-muted); font-size: 0.85rem; text-transform: uppercase; }
.schedule-table tbody tr:hover { background: var(--schedule-surface-elevated); }
.schedule-recurrence-badge { font-size: 0.85rem; color: var(--schedule-accent); }
.schedule-channel-badge { display: inline-block; padding: 0.2rem 0.5rem; background: rgba(99,102,241,0.2); border-radius: 6px; font-size: 0.75rem; margin-right: 0.25rem; }
.schedule-status-badge { padding: 0.25rem 0.6rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.schedule-status-pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
.schedule-status-active { background: rgba(34,211,238,0.2); color: #22d3ee; }
.schedule-status-sent { background: rgba(16,185,129,0.2); color: #10b981; }
.schedule-status-completed { background: rgba(99,102,241,0.2); color: #6366f1; }
.schedule-status-cancelled { background: rgba(139,139,167,0.2); color: var(--schedule-muted); }
.schedule-btn-danger { color: #ef4444 !important; border-color: rgba(239,68,68,0.4) !important; }
.schedule-btn-danger:hover { background: rgba(239,68,68,0.1) !important; }
.schedule-empty { text-align: center; padding: 3rem 2rem; }
.schedule-empty-icon { font-size: 3rem; color: var(--schedule-muted); margin-bottom: 1rem; }
.schedule-empty h3 { margin-bottom: 0.5rem; }
.schedule-pagination { padding: 1rem; border-top: 1px solid var(--schedule-border); }
body:not(.theme-dark) .schedule-table th, body:not(.theme-dark) .schedule-table td { border-color: rgba(99,102,241,0.1); }
</style>
@endpush
@endsection
