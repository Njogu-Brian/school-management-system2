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
    $lastPromisedValue = $lastPromised instanceof \Carbon\Carbon
        ? $lastPromised->format('Y-m-d')
        : ($lastPromised ? \Carbon\Carbon::parse($lastPromised)->format('Y-m-d') : '');
    $isFamily = !empty($group['is_family']);
    $childNames = $group['child_names'] ?? [];
    $parentNames = $group['parent_names'] ?? [];
    $parentPhone = $group['parent_phone'] ?? null;
    $familyId = $group['family_id'] ?? null;
    $firstChild = ($group['children'] ?? collect())->first();
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
                    @include('finance.fee_balances.partials.parent_contacts', ['contacts' => $group['parent_contacts'] ?? []])
                    @if($lastPromised)
                        <span>
                            <i class="bi bi-calendar-event"></i>
                            Promised {{ $lastPromised instanceof \Carbon\Carbon ? $lastPromised->format('d M Y') : \Carbon\Carbon::parse($lastPromised)->format('d M Y') }}
                        </span>
                    @endif
                </div>

                <div class="fee-family-controls d-flex flex-wrap gap-2 align-items-center mt-2">
                    @if($familyId)
                        <div class="fee-promise-form">
                            <label class="visually-hidden" for="family-fiscal-{{ $familyId }}">Family fiscal task</label>
                            <select
                                id="family-fiscal-{{ $familyId }}"
                                name="families[{{ $familyId }}][fiscal_task]"
                                class="form-select form-select-sm"
                            >
                                <option value="" {{ $groupTask === 'none' ? 'selected' : '' }}>Family task: unset</option>
                                <option value="green" {{ $groupTask === 'green' ? 'selected' : '' }}>Green</option>
                                <option value="yellow" {{ $groupTask === 'yellow' ? 'selected' : '' }}>Yellow</option>
                                <option value="red" {{ $groupTask === 'red' ? 'selected' : '' }}>Red</option>
                            </select>
                        </div>

                        <div class="fee-promise-form">
                            <input type="hidden" name="families[{{ $familyId }}][current_promise_date]" value="{{ $lastPromisedValue }}">
                            <label class="visually-hidden" for="family-promise-{{ $familyId }}">Family promise date</label>
                            <input
                                type="date"
                                id="family-promise-{{ $familyId }}"
                                name="families[{{ $familyId }}][promise_date]"
                                class="form-control form-control-sm"
                                value="{{ $lastPromisedValue }}"
                            >
                        </div>

                        @include('finance.partials.fee-notes-launcher', [
                            'familyId' => $familyId,
                            'title' => $group['label'] ?? 'Family',
                            'noteCount' => $group['note_count'] ?? 0,
                            'scope' => 'family',
                            'buttonClass' => 'btn btn-sm btn-outline-secondary',
                        ])
                    @endif
                </div>
            </div>
            <div class="fee-family-totals">
                <div class="fee-metric">
                    <span class="fee-metric-label">Previous terms</span>
                    <span class="fee-metric-value">Ksh {{ number_format($group['prior_term_balance'] ?? 0, 0) }}</span>
                </div>
                <div class="fee-metric">
                    <span class="fee-metric-label">This term</span>
                    <span class="fee-metric-value">Ksh {{ number_format($group['current_term_balance'] ?? 0, 0) }}</span>
                </div>
                <div class="fee-metric fee-metric-emphasis">
                    <span class="fee-metric-label">Total owed</span>
                    <span class="fee-metric-value {{ ($group['balance'] ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                        Ksh {{ number_format($group['balance'], 0) }}
                    </span>
                </div>
                @if($familyId)
                    <a href="{{ route('finance.student-statements.family.show', $familyId) }}"
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
