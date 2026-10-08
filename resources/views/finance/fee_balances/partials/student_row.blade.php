@php
    $inFamilyGroup = $inFamilyGroup ?? false;
    $fiscalTask = $student['fiscal_task'] ?? 'none';
    $fiscalLabels = [
        'green' => 'On track',
        'yellow' => 'Due soon',
        'red' => 'Follow up',
        'none' => '—',
    ];
    $fiscalLabel = $fiscalLabels[$fiscalTask] ?? '—';
    $lastPromised = $student['last_promised'] ?? null;
    $lastPromisedValue = $lastPromised instanceof \Carbon\Carbon
        ? $lastPromised->format('Y-m-d')
        : ($lastPromised ? \Carbon\Carbon::parse($lastPromised)->format('Y-m-d') : '');
    $lastPaymentDate = $student['last_payment_date'] ?? null;
    $lastPaymentAmount = (float) ($student['last_payment_amount'] ?? 0);
    $hasUnclearedBbf = !empty($student['has_uncleared_bbf']);
    $statusColors = [
        'paid' => 'success',
        'partial' => 'warning',
        'unpaid' => 'danger',
        'not_invoiced' => 'secondary',
    ];
    $statusColor = $statusColors[$student['payment_status']] ?? 'secondary';
@endphp

<div class="fee-child-card {{ $inFamilyGroup ? 'is-child' : '' }} {{ $student['is_in_school'] && $student['balance'] > 1000 ? 'highlight-row' : '' }}">
    <div class="fee-child-identity">
        <div class="fee-child-name-row">
            <strong class="fee-child-name">{{ $student['full_name'] }}</strong>
            <span class="finance-badge badge-{{ $statusColor }}">{{ ucfirst(str_replace('_', ' ', $student['payment_status'])) }}</span>
        </div>
        <div class="fee-child-sub">
            <span class="fee-adm">{{ $student['admission_number'] }}</span>
            @if(!empty($student['classroom']))
                <span>{{ $student['classroom'] }}@if(!empty($student['stream'])) · {{ $student['stream'] }}@endif</span>
            @endif
            @if(!empty($student['parent_contacts']) && !$inFamilyGroup)
                <div class="mt-2">
                    @include('finance.fee_balances.partials.parent_contacts', ['contacts' => $student['parent_contacts']])
                </div>
            @endif
        </div>
    </div>

    <div class="fee-child-figures">
        <div class="fee-metric">
            <span class="fee-metric-label">Previous terms</span>
            <span class="fee-metric-value">Ksh {{ number_format($student['prior_term_balance'] ?? 0, 0) }}</span>
        </div>
        <div class="fee-metric">
            <span class="fee-metric-label">This term</span>
            <span class="fee-metric-value">Ksh {{ number_format($student['current_term_balance'] ?? 0, 0) }}</span>
        </div>
        <div class="fee-metric fee-metric-emphasis">
            <span class="fee-metric-label">Total owed</span>
            <span class="fee-metric-value {{ $student['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                Ksh {{ number_format($student['balance'], 0) }}
            </span>
        </div>
    </div>

    <div class="fee-child-status">
        @unless($inFamilyGroup)
            @if($fiscalTask !== 'none')
                <span class="fiscal-task-badge fiscal-task-{{ $fiscalTask }}" title="Fiscal task (manual)">
                    <span class="fiscal-dot"></span>{{ $fiscalLabel }}
                </span>
            @endif

            <div class="fee-promise-form" title="Set fiscal task">
                @if(!empty($student['family_id']))
                    <input type="hidden" name="students[{{ $student['id'] }}][family_id]" value="{{ $student['family_id'] }}">
                @endif
                <label class="visually-hidden" for="fiscal-{{ $student['id'] }}">Fiscal task</label>
                <select
                    id="fiscal-{{ $student['id'] }}"
                    name="students[{{ $student['id'] }}][fiscal_task]"
                    class="form-select form-select-sm"
                >
                    <option value="" {{ $fiscalTask === 'none' ? 'selected' : '' }}>Task: unset</option>
                    <option value="green" {{ $fiscalTask === 'green' ? 'selected' : '' }}>Green</option>
                    <option value="yellow" {{ $fiscalTask === 'yellow' ? 'selected' : '' }}>Yellow</option>
                    <option value="red" {{ $fiscalTask === 'red' ? 'selected' : '' }}>Red</option>
                </select>
            </div>

            @if($lastPromised)
                <span class="fee-chip" title="Last promised date">
                    <i class="bi bi-calendar-event"></i>
                    Promised {{ $lastPromised instanceof \Carbon\Carbon ? $lastPromised->format('d M Y') : \Carbon\Carbon::parse($lastPromised)->format('d M Y') }}
                </span>
            @endif

            <div class="fee-promise-form">
                <input type="hidden" name="students[{{ $student['id'] }}][current_promise_date]" value="{{ $lastPromisedValue }}">
                <label class="visually-hidden" for="promise-{{ $student['id'] }}">Promise date</label>
                <input
                    type="date"
                    id="promise-{{ $student['id'] }}"
                    name="students[{{ $student['id'] }}][promise_date]"
                    class="form-control form-control-sm"
                    value="{{ $lastPromisedValue }}"
                >
            </div>
        @endunless

        @if($lastPaymentDate)
            <span class="fee-chip fee-chip-info" title="Last payment">
                <i class="bi bi-cash-coin"></i>
                Last pay {{ $lastPaymentDate instanceof \Carbon\Carbon ? $lastPaymentDate->format('d M Y') : \Carbon\Carbon::parse($lastPaymentDate)->format('d M Y') }}
                @if($lastPaymentAmount > 0)
                    · Ksh {{ number_format($lastPaymentAmount, 0) }}
                @endif
            </span>
        @endif

        @if($hasUnclearedBbf)
            <span class="fee-chip fee-chip-warn" title="Uncleared balance brought forward">
                BBF Ksh {{ number_format($student['balance_brought_forward_balance'], 0) }}
            </span>
        @endif

        @php
            $todayStatus = $student['today_status'] ?? 'unmarked';
        @endphp
        @if($todayStatus === 'absent')
            <span class="fee-chip fee-chip-muted" title="Marked absent today">
                <i class="bi bi-x-circle"></i> Absent today
            </span>
        @elseif($todayStatus === 'late')
            <span class="fee-chip fee-chip-warn" title="Marked late today">
                <i class="bi bi-clock"></i> Late today
            </span>
        @elseif($todayStatus === 'present')
            <span class="fee-chip fee-chip-ok" title="Marked present today">
                <i class="bi bi-check-circle"></i> Present today
            </span>
        @else
            <span class="fee-chip fee-chip-unmarked" title="Attendance not taken today">
                <i class="bi bi-dash-circle"></i> Unmarked
            </span>
        @endif
        <span class="fee-chip" title="Attendance since term start">
            {{ $student['attendance_rate'] }}% this term
        </span>

        @if($student['has_payment_plan'])
            <span class="fee-chip fee-chip-info">
                <i class="bi bi-calendar-check"></i> Plan {{ $student['payment_plan_progress'] }}%
                @if($student['next_installment_date'])
                    · next {{ $student['next_installment_date']->format('M d') }}
                @endif
            </span>
        @endif
    </div>

    <div class="fee-child-actions table-actions">
        <div class="btn-group btn-group-sm">
            @if($student['invoice_id'])
                <a href="{{ route('finance.invoices.show', $student['invoice_id']) }}"
                   class="btn btn-outline-primary" title="View Invoice">
                    <i class="bi bi-file-text"></i>
                </a>
            @endif
            <a href="{{ route('finance.student-statements.show', $student['id']) }}"
               class="btn btn-outline-info" title="View Statement">
                <i class="bi bi-receipt"></i>
            </a>
            @if($student['balance'] > 0 && !$student['has_payment_plan'])
                <a href="{{ route('finance.fee-payment-plans.create') }}?student_id={{ $student['id'] }}"
                   class="btn btn-outline-success" title="Create Payment Plan">
                    <i class="bi bi-calendar-plus"></i>
                </a>
            @endif
        </div>
        @include('finance.partials.fee-notes-launcher', [
            'studentId' => $student['id'],
            'familyId' => $student['family_id'] ?? null,
            'title' => $student['full_name'],
            'noteCount' => $student['note_count'] ?? 0,
            'scope' => 'student',
            'buttonClass' => 'btn btn-sm btn-outline-secondary',
        ])
    </div>
</div>
