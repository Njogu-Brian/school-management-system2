@php
    $fiscalLabels = [
        'green' => 'On track',
        'yellow' => 'Due soon',
        'red' => 'Follow up',
        'none' => '—',
    ];
    $groupTask = $group['fiscal_task'] ?? 'none';
    $groupTaskLabel = $fiscalLabels[$groupTask] ?? '—';
    $lastPromised = $group['last_promised'] ?? null;
@endphp

@if(!empty($group['is_family']))
<tr class="family-group-header">
    <td colspan="3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary">
                <i class="bi bi-people-fill"></i> {{ $group['label'] }}
            </span>
            <small class="text-muted">
                <i class="bi bi-telephone"></i> {{ $group['parent_phone'] ?? 'N/A' }}
            </small>
            @if(!empty($group['father_name']) || !empty($group['mother_name']))
                <small class="text-muted">
                    {{ collect([$group['father_name'] ?? null, $group['mother_name'] ?? null])->filter()->implode(' / ') }}
                </small>
            @endif
        </div>
    </td>
    <td class="text-end">
        <strong>Ksh {{ number_format($group['total_invoiced'], 2) }}</strong>
        <br><small class="text-muted">Family invoiced</small>
    </td>
    <td class="text-end text-success">
        <strong>Ksh {{ number_format($group['total_paid'], 2) }}</strong>
        <br><small class="text-muted">Family paid</small>
    </td>
    <td class="text-end">
        <strong class="{{ ($group['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
            Ksh {{ number_format($group['balance'], 2) }}
        </strong>
        <br><small class="text-muted">Family owing</small>
    </td>
    <td class="text-center">
        <span class="text-muted small">Family</span>
    </td>
    <td class="text-center" colspan="2">
        <span class="text-muted">—</span>
    </td>
    <td class="text-center">
        @if($lastPromised)
            <strong>{{ $lastPromised instanceof \Carbon\Carbon ? $lastPromised->format('d M Y') : \Carbon\Carbon::parse($lastPromised)->format('d M Y') }}</strong>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-center">
        @if($groupTask !== 'none')
            <span class="fiscal-task-badge fiscal-task-{{ $groupTask }}" title="Family fiscal task">
                <span class="fiscal-dot"></span>
                {{ $groupTaskLabel }}
            </span>
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td colspan="3"></td>
    <td class="text-center">
        @if(!empty($group['family_id']))
            <a href="{{ route('finance.student-statements.family.show', $group['family_id']) }}"
               class="btn btn-outline-info btn-sm" title="Family statement">
                <i class="bi bi-receipt"></i>
            </a>
        @endif
    </td>
</tr>
@endif

@foreach($group['children'] as $student)
    @include('finance.fee_balances.partials.student_row', [
        'student' => $student,
        'inFamilyGroup' => !empty($group['is_family']),
    ])
@endforeach
