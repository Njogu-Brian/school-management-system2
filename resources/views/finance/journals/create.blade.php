@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'New Credit / Debit Adjustment',
        'icon' => 'bi bi-arrow-left-right',
        'subtitle' => 'Create credit or debit adjustments for student invoices',
        'actions' => '<a href="' . route('finance.journals.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Adjustments</a>'
    ])

    @includeIf('finance.invoices.partials.alerts')

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-file-earmark-text me-2"></i> Adjustment Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.journals.store') }}" class="row g-3">
                @csrf

                {{-- Student picker --}}
                <div class="col-md-12">
                    <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                    @include('partials.student_live_search', [
                        'hiddenInputId' => 'selectedStudentId',
                        'displayInputId' => 'selectedStudentName',
                        'resultsId' => 'journalStudentResults',
                        'placeholder' => 'Search by name or admission #',
                        'inputClass' => 'finance-form-control',
                        'initialLabel' => old('student_id') ? optional(\App\Models\Student::find(old('student_id')))->search_display : ''
                    ])
                    <small class="text-muted">Pick a student using the search box</small>
                </div>

                <div class="col-md-6 col-lg-4">
                    <label class="finance-form-label">Votehead <span class="text-danger">*</span></label>
                    <select name="votehead_id" id="voteheadSelect" class="finance-form-select" required disabled>
                        <option value="">-- Select Student First --</option>
                    </select>
                    <small class="text-muted" id="voteheadHelp">Select a student to see available voteheads from their invoice</small>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                    <input type="number" name="year" class="finance-form-control"
                           value="{{ old('year', now()->year) }}" required>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Term <span class="text-danger">*</span></label>
                    <select name="term" class="finance-form-select" required>
                        @php
                            $currentTerm = \App\Models\Term::where('is_current', true)->first();
                            $defaultTerm = $currentTerm ? (int) preg_replace('/[^0-9]/', '', $currentTerm->name) : 1;
                        @endphp
                        @for($i=1;$i<=3;$i++)
                            <option value="{{ $i }}" @selected(old('term', $defaultTerm)==$i)>Term {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="finance-form-select" required>
                        <option value="debit"  @selected(old('type')==='debit')>Debit (+)</option>
                        <option value="credit" @selected(old('type')==='credit')>Credit (-)</option>
                    </select>
                </div>

                <div class="col-md-6 col-lg-2">
                    <label class="finance-form-label">Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Ksh</span>
                        <input type="number" step="0.01" min="0.01" name="amount"
                               class="finance-form-control" value="{{ old('amount') }}" required>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <label class="finance-form-label">Effective Date</label>
                    <input type="date" name="effective_date" class="finance-form-control"
                           value="{{ old('effective_date') }}">
                    <small class="text-muted">Leave empty to apply today</small>
                </div>

                <div class="col-md-12">
                    <label class="finance-form-label">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="finance-form-control" maxlength="255"
                           value="{{ old('reason') }}" required placeholder="Enter reason for this adjustment">
                </div>

                <div class="col-12 mt-4">
                    <button class="btn btn-finance btn-finance-primary" type="submit">
                        <i class="bi bi-check-circle"></i> Create & Apply
                    </button>

                    <a class="btn btn-finance btn-finance-outline" href="{{ route('finance.journals.index') }}">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>

                    <a class="btn btn-finance btn-finance-outline" href="{{ route('finance.journals.bulk.form') }}">
                        <i class="bi bi-upload"></i> Bulk Import (Excel/CSV)
                    </a>
                </div>
            </form>
        </div>
    </div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentIdInput = document.getElementById('selectedStudentId');
    const studentNameInput = document.getElementById('selectedStudentName');
    const voteheadSelect = document.getElementById('voteheadSelect');
    const voteheadHelp = document.getElementById('voteheadHelp');
    const yearInput = document.querySelector('input[name="year"]');
    const termSelect = document.querySelector('select[name="term"]');
    
    // Function to load voteheads from invoice
    function loadVoteheadsFromInvoice() {
        const studentId = studentIdInput.value;
        const year = yearInput.value;
        const term = termSelect.value;
        
        if (!studentId || !year || !term) {
            voteheadSelect.innerHTML = '<option value="">-- Select Student, Year, and Term First --</option>';
            voteheadSelect.disabled = true;
            return;
        }
        
        // Show loading state
        voteheadSelect.disabled = true;
        voteheadSelect.innerHTML = '<option value="">Loading voteheads...</option>';
        voteheadHelp.textContent = 'Loading voteheads from invoice...';
        
        // Fetch voteheads from invoice
        fetch(`{{ route('finance.journals.get-invoice-voteheads') }}?student_id=${studentId}&year=${year}&term=${term}`)
            .then(response => response.json())
            .then(data => {
                voteheadSelect.innerHTML = '<option value="">-- Select Votehead --</option>';
                
                if (data.voteheads && data.voteheads.length > 0) {
                    data.voteheads.forEach(vh => {
                        const option = document.createElement('option');
                        option.value = vh.id;
                        option.textContent = `${vh.name} (Current: Ksh ${parseFloat(vh.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
                        voteheadSelect.appendChild(option);
                    });
                    voteheadSelect.disabled = false;
                    voteheadHelp.textContent = `Found ${data.voteheads.length} votehead(s) in invoice`;
                } else {
                    voteheadSelect.innerHTML = '<option value="">No invoice found or no voteheads in invoice</option>';
                    voteheadSelect.disabled = true;
                    voteheadHelp.textContent = 'No invoice found for this student, year, and term. Please create an invoice first.';
                }
            })
            .catch(error => {
                console.error('Error loading voteheads:', error);
                voteheadSelect.innerHTML = '<option value="">Error loading voteheads</option>';
                voteheadSelect.disabled = true;
                voteheadHelp.textContent = 'Error loading voteheads. Please try again.';
            });
    }
    
    // Trigger loading when live search selects a student
    window.addEventListener('student-selected', (event) => {
        if (event.detail?.id) {
            studentIdInput.value = event.detail.id;
            loadVoteheadsFromInvoice();
        }
    });
    studentIdInput.addEventListener('change', loadVoteheadsFromInvoice);
    yearInput.addEventListener('change', loadVoteheadsFromInvoice);
    termSelect.addEventListener('change', loadVoteheadsFromInvoice);
    
    // Load voteheads if student is already selected (on page load with old input)
    if (studentIdInput.value && yearInput.value && termSelect.value) {
        loadVoteheadsFromInvoice();
    }
});
</script>
@endpush
@endsection

