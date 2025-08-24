{{-- Student-Based Optional Fees --}}

<form method="GET" action="{{ route('finance.optional_fees.student_view') }}" class="row g-3 mb-4" id="studentForm">
    <div class="col-md-5">
        <label class="form-label">Search Student</label>
        <select id="studentSearch" name="student_id" class="form-select" required style="width: 100%;">
            @if(request('student_id') && isset($student))
                <option value="{{ $student->id }}" selected>{{ $student->full_name }} ({{ $student->admission_number }})</option>
            @endif
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Term</label>
        <select name="term" id="termSelect" class="form-select" required>
            <option value="">Select</option>
            @for($i = 1; $i <= 3; $i++)
                <option value="{{ $i }}" {{ request('term') == $i ? 'selected' : '' }}>Term {{ $i }}</option>
            @endfor
        </select>
    </div>

    <div class="col-md-2">
        <label class="form-label">Year</label>
        <input type="number" name="year" id="yearInput" value="{{ request('year') ?? now()->year }}" class="form-control" required>
    </div>

    {{-- Hidden submit button (not used directly; we auto-submit via JS) --}}
    <button type="submit" class="d-none" id="autoSubmitBtn">Submit</button>
</form>

{{-- Show results only when all three inputs are present --}}
@if(request()->filled(['student_id', 'term', 'year']) && isset($student))
    <div class="card">
        <div class="card-header">
            <strong>Optional Fees for:</strong> {{ $student->full_name }} ({{ $student->admission_number }})
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('finance.optional_fees.saveStudent') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="term" value="{{ request('term') }}">
                <input type="hidden" name="year" value="{{ request('year') }}">

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Votehead</th>
                            <th>Billing Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voteheads as $votehead)
                            @php $status = $statuses[$votehead->id] ?? 'exempt'; @endphp
                            <tr>
                                <td>{{ $votehead->name }}</td>
                                <td>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="bill" {{ $status == 'bill' ? 'checked' : '' }}>
                                        <label class="form-check-label">Bill</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="exempt" {{ $status == 'exempt' ? 'checked' : '' }}>
                                        <label class="form-check-label">Exempt</label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button class="btn btn-success" type="submit">Save</button>
            </form>
        </div>
    </div>
@endif

@push('scripts')
    {{-- jQuery (if not already globally included) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- Select2 assets --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

    <script>
    $(function () {
        const $form    = $('#studentForm');
        const $student = $('#studentSearch');
        const $term    = $('#termSelect');
        const $year    = $('#yearInput');

        // Initialize Select2 and render dropdown inside the form (tab-pane)
        $student.select2({
            width: '100%',
            placeholder: 'Search by name or admission no',
            allowClear: true,
            minimumInputLength: 2,
            dropdownParent: $form,              // ✅ keep the dropdown in the tab
            ajax: {
                url: '{{ route("api.students.search") }}',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({
                    results: data.map(s => ({
                        id: s.id,
                        text: (s.full_name ?? '') + ' (' + (s.admission_number ?? '-') + ')'
                    }))
                }),
                cache: true
            }
        });

        // Open dropdown on focus-click
        $student.on('select2:open', () => {
            document.querySelector('.select2-search__field')?.focus();
        });

        // Auto-submit when all three inputs are present
        function ready() { return $student.val() && $term.val() && $year.val(); }
        $student.add($term).add($year).on('change', function () {
            if (ready()) $form.trigger('submit');
        });
    });
    </script>
@endpush

