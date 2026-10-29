@php
    $selectedYear = request('year');
    $selectedTerm = request('term');
    $yearCol = $yearCol ?? 'col-md-6 col-lg-2';
    $termCol = $termCol ?? 'col-md-6 col-lg-2';
    $yearSelectId = $yearSelectId ?? 'filterYearSelect';
    $termSelectId = $termSelectId ?? 'filterTermSelect';
@endphp

<div class="{{ $yearCol }}">
    <label class="finance-form-label" for="{{ $yearSelectId }}">Year</label>
    <select name="year" id="{{ $yearSelectId }}" class="finance-form-select">
        <option value="">All Years</option>
        @foreach($academicYears ?? [] as $ay)
            <option value="{{ $ay->year }}" @selected((string) $selectedYear === (string) $ay->year)>
                {{ $ay->year }}{{ $ay->is_active ? ' (current)' : '' }}
            </option>
        @endforeach
    </select>
</div>
<div class="{{ $termCol }}">
    <label class="finance-form-label" for="{{ $termSelectId }}">Term</label>
    <select name="term" id="{{ $termSelectId }}" class="finance-form-select">
        <option value="">All Terms</option>
        @if(filled($selectedYear))
            @foreach(($termsByYear[(string) $selectedYear] ?? []) as $termOption)
                <option value="{{ $termOption['number'] }}" @selected((string) $selectedTerm === (string) $termOption['number'])>
                    {{ $termOption['name'] }}
                </option>
            @endforeach
        @endif
    </select>
</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[name="year"]').forEach(function (yearSelect) {
        const form = yearSelect.closest('form');
        if (!form) return;
        const termSelect = form.querySelector('select[name="term"]');
        if (!termSelect || yearSelect.dataset.termCascadeBound) return;
        yearSelect.dataset.termCascadeBound = '1';

        const termsByYear = @json($termsByYear ?? new \stdClass());
        const previousTerm = @json((string) request('term', ''));

        function refreshTerms() {
            const year = String(yearSelect.value || '');
            const terms = termsByYear[year] || [];
            const keep = termSelect.value || previousTerm;
            termSelect.innerHTML = '';

            const all = document.createElement('option');
            all.value = '';
            all.textContent = 'All Terms';
            termSelect.appendChild(all);

            if (!year) {
                termSelect.value = '';
                return;
            }

            terms.forEach(function (term) {
                const opt = document.createElement('option');
                opt.value = String(term.number);
                opt.textContent = term.name;
                termSelect.appendChild(opt);
            });

            if (keep && [...termSelect.options].some(function (opt) { return opt.value === String(keep); })) {
                termSelect.value = String(keep);
            } else {
                termSelect.value = '';
            }
        }

        yearSelect.addEventListener('change', refreshTerms);
        refreshTerms();
    });
});
</script>
@endpush
@endonce
