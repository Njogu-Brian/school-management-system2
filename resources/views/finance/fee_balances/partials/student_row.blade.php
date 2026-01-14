<tr class="{{ $student['is_in_school'] && $student['balance'] > 1000 ? 'highlight-row' : '' }}">
    <td><strong>{{ $student['admission_number'] }}</strong></td>
    <td>
        <div>
            <strong>{{ $student['full_name'] }}</strong>
            <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $student['parent_phone'] }}</small>
        </div>
    </td>
    <td>
        {{ $student['classroom'] }}
        @if($student['stream'])
            <br><small class="text-muted">{{ $student['stream'] }}</small>
        @endif
    </td>
    <td class="text-end">
        <strong>Ksh {{ number_format($student['total_invoiced'], 2) }}</strong>
    </td>
    <td class="text-end text-success">
        <strong>Ksh {{ number_format($student['total_paid'], 2) }}</strong>
    </td>
    <td class="text-end">
        <strong class="{{ $student['balance'] > 0 ? 'text-danger' : 'text-success' }}">
            Ksh {{ number_format($student['balance'], 2) }}
        </strong>
        @if($student['balance'] > 0 && $student['total_invoiced'] > 0)
            <br><small class="text-muted">{{ $student['balance_percentage'] }}% owing</small>
        @endif
    </td>
    <td class="text-center">
        @php
            $statusColors = [
                'paid' => 'success',
                'partial' => 'warning',
                'unpaid' => 'danger',
                'not_invoiced' => 'secondary'
            ];
            $statusColor = $statusColors[$student['payment_status']] ?? 'secondary';
        @endphp
        <span class="finance-badge badge-{{ $statusColor }}">
            {{ ucfirst(str_replace('_', ' ', $student['payment_status'])) }}
        </span>
    </td>
    <td class="text-center">
        <div class="mb-1">
            <strong>{{ $student['attendance_rate'] }}%</strong>
        </div>
        <div class="progress-thin">
            <div class="progress-bar {{ $student['attendance_rate'] >= 75 ? 'bg-success' : ($student['attendance_rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                 style="width: {{ $student['attendance_rate'] }}%"></div>
        </div>
        <small class="text-muted">{{ $student['days_present'] }}/{{ $student['attendance_days'] }} days</small>
    </td>
    <td class="text-center">
        @if($student['is_in_school'])
            <span class="badge-in-school">
                <i class="bi bi-check-circle"></i> In School
            </span>
        @else
            <span class="badge-not-reported">
                <i class="bi bi-x-circle"></i> Not Reported
            </span>
        @endif
    </td>
    <td class="text-center">
        @if($student['has_payment_plan'])
            <span class="badge-has-plan">
                <i class="bi bi-calendar-check"></i> Has Plan
            </span>
            <br><small class="text-muted">{{ $student['payment_plan_progress'] }}% paid</small>
            @if($student['next_installment_date'])
                <br><small class="text-muted">Next: {{ $student['next_installment_date']->format('M d') }}</small>
            @endif
        @else
            <span class="text-muted">-</span>
        @endif
    </td>
    <td class="text-center table-actions">
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
    </td>
</tr>

