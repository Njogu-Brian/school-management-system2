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
    $isFamily = !empty($group['is_family']);
    $childNames = $group['child_names'] ?? [];
    $parentNames = $group['parent_names'] ?? [];
    $parentPhone = $group['parent_phone'] ?? null;
@endphp

<article class="fee-entity-card {{ $isFamily ? 'is-family' : '' }} {{ (($group['balance'] ?? 0) > 1000) ? 'has-highlight' : '' }}">
    @if($isFamily)
        <header class="fee-family-head">
            <div class="fee-family-main">
                <div class="fee-family-title-row">
                    <span class="fee-family-badge"><i class="bi bi-people-fill"></i> {{ $group['label'] }}</span>
                    @if($groupTask !== 'none')
                        <span class="fiscal-task-badge fiscal-task-{{ $groupTask }}">
                            <span class="fiscal-dot"></span>{{ $groupTaskLabel }}
                        </span>
                    @endif
                </div>
                <div class="fee-family-names">
                    {{ implode(' · ', $childNames) }}
                </div>
                <div class="fee-family-meta">
                    @if($parentPhone)
                        <span><i class="bi bi-telephone"></i> {{ $parentPhone }}</span>
                    @endif
                    @if(!empty($parentNames))
                        <span>{{ implode(' / ', $parentNames) }}</span>
                    @endif
                    @if($lastPromised)
                        <span>
                            <i class="bi bi-calendar-event"></i>
                            Promised {{ $lastPromised instanceof \Carbon\Carbon ? $lastPromised->format('d M Y') : \Carbon\Carbon::parse($lastPromised)->format('d M Y') }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="fee-family-totals">
                <div class="fee-metric">
                    <span class="fee-metric-label">Term invoiced</span>
                    <span class="fee-metric-value">Ksh {{ number_format($group['total_invoiced'], 0) }}</span>
                </div>
                <div class="fee-metric">
                    <span class="fee-metric-label">On term invoices</span>
                    <span class="fee-metric-value text-success">Ksh {{ number_format($group['total_paid'], 0) }}</span>
                </div>
                <div class="fee-metric">
                    <span class="fee-metric-label">Paid in term period</span>
                    <span class="fee-metric-value">Ksh {{ number_format($group['paid_in_term_period'] ?? 0, 0) }}</span>
                </div>
                <div class="fee-metric fee-metric-emphasis">
                    <span class="fee-metric-label">Family balance</span>
                    <span class="fee-metric-value {{ ($group['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        Ksh {{ number_format($group['balance'], 0) }}
                    </span>
                </div>
                @if(!empty($group['family_id']))
                    <a href="{{ route('finance.student-statements.family.show', $group['family_id']) }}"
                       class="btn btn-sm btn-outline-info fee-family-action">
                        <i class="bi bi-receipt"></i> Family statement
                    </a>
                @endif
            </div>
        </header>
    @endif

    <div class="fee-children">
        @foreach($group['children'] as $student)
            @include('finance.fee_balances.partials.student_row', [
                'student' => $student,
                'inFamilyGroup' => $isFamily,
            ])
        @endforeach
    </div>
</article>
