@extends('layouts.app')

@section('content')

    @include('finance.partials.header', [
        'title' => 'Allocate Discount',
        'icon' => 'bi bi-person-plus',
        'subtitle' => 'Assign a discount template to a student',
        'actions' => '<a href="' . route('finance.discounts.templates.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-file-earmark-text"></i> Templates</a><a href="' . route('finance.discounts.allocations.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-list-check"></i> Allocations</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.discounts.allocate.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-header d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i> <span>Allocation Details</span>
                    </div>
                    <div class="finance-card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="finance-form-label">Discount Template <span class="text-danger">*</span></label>
                                <select name="discount_template_id" id="discount_template_id" class="finance-form-select @error('discount_template_id') is-invalid @enderror" required>
                                    <option value="">-- Select Template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('discount_template_id', request('template')) == $template->id ? 'selected' : '' }}
                                            data-scope="{{ $template->scope }}"
                                            data-type="{{ $template->type }}"
                                            data-value="{{ $template->value }}">
                                            {{ $template->name }} 
                                            @if($template->type === 'percentage')
                                                ({{ $template->value }}%)
                                            @else
                                                (Ksh {{ number_format($template->value, 2) }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('discount_template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select a discount template to allocate</small>
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                                <select name="student_id" class="finance-form-select @error('student_id') is-invalid @enderror" required>
                                    <option value="">-- Select Student --</option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                            {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Academic Year <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="finance-form-select @error('academic_year_id') is-invalid @enderror" required>
                                    <option value="">-- Select Year --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYear?->id) == $year->id ? 'selected' : '' }}>
                                            {{ $year->year }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                                <input type="number" name="year" class="finance-form-control @error('year') is-invalid @enderror" 
                                       value="{{ old('year', $currentYear?->year) }}" 
                                       placeholder="e.g., 2025" required>
                                @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Term <span class="text-danger">*</span></label>
                                <select name="term" class="finance-form-select @error('term') is-invalid @enderror" required>
                                    <option value="">-- Select Term --</option>
                                    <option value="1" {{ old('term') == '1' ? 'selected' : '' }}>Term 1</option>
                                    <option value="2" {{ old('term') == '2' ? 'selected' : '' }}>Term 2</option>
                                    <option value="3" {{ old('term') == '3' ? 'selected' : '' }}>Term 3</option>
                                </select>
                                @error('term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="finance-form-label">Start Date</label>
                                <input type="date" name="start_date" class="finance-form-control @error('start_date') is-invalid @enderror" 
                                       value="{{ old('start_date', date('Y-m-d')) }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12" id="votehead_selector" style="display: none;">
                                <label class="finance-form-label">Voteheads <span class="text-danger">*</span> <span class="text-muted">(Select one or more)</span></label>
                                <select name="votehead_ids[]" class="finance-form-select @error('votehead_ids') is-invalid @enderror" multiple size="6">
                                    @foreach($voteheads as $votehead)
                                        <option value="{{ $votehead->id }}" {{ in_array($votehead->id, old('votehead_ids', [])) ? 'selected' : '' }}>
                                            {{ $votehead->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('votehead_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple voteheads</small>
                            </div>

                            <div class="col-md-12">
                                <label class="finance-form-label">End Date</label>
                                <input type="date" name="end_date" class="finance-form-control @error('end_date') is-invalid @enderror" 
                                       value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank to use template expiry</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                    <div class="finance-card-header secondary d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle"></i> <span>Template Info</span>
                    </div>
                    <div class="finance-card-body p-4" id="template_info">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Select a template to see details
                        </div>
                    </div>
                </div>

                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-body p-4 d-grid gap-2">
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-check-circle"></i> Allocate Discount
                    </button>
                    <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="btn btn-finance btn-finance-success">
                        <i class="bi bi-people"></i> Bulk Allocate Sibling Discounts
                    </a>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-finance btn-finance-outline">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.getElementById('discount_template_id');
    const voteheadSelector = document.getElementById('votehead_selector');
    const templateInfo = document.getElementById('template_info');

    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const scope = selectedOption.getAttribute('data-scope');
        const type = selectedOption.getAttribute('data-type');
        const value = selectedOption.getAttribute('data-value');

        // Show/hide votehead selector
        if (scope === 'votehead') {
            voteheadSelector.style.display = 'block';
            voteheadSelector.querySelector('select').required = true;
        } else {
            voteheadSelector.style.display = 'none';
            voteheadSelector.querySelector('select').required = false;
        }

        // Update template info
        if (this.value) {
            const templateName = selectedOption.text.split('(')[0].trim();
            templateInfo.innerHTML = `
                <div class="mb-2">
                    <strong>Template:</strong><br>
                    <span class="badge bg-info">${templateName}</span>
                </div>
                <div class="mb-2">
                    <strong>Scope:</strong><br>
                    <span class="badge bg-primary">${scope ? scope.charAt(0).toUpperCase() + scope.slice(1) : 'N/A'}</span>
                </div>
                <div class="mb-2">
                    <strong>Value:</strong><br>
                    <span class="text-primary fw-bold">${type === 'percentage' ? value + '%' : 'Ksh ' + parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </div>
            `;
        } else {
            templateInfo.innerHTML = `
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Select a template to see details
                </div>
            `;
        }
    });

    // Trigger on page load if template is pre-selected
    if (templateSelect.value) {
        templateSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection
