@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0">
                <i class="bi bi-person-check"></i> Allocate Discount
            </h3>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.discounts.allocate.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Allocation Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Discount Template <span class="text-danger">*</span></label>
                                <select name="discount_template_id" id="discount_template_id" class="form-select @error('discount_template_id') is-invalid @enderror" required>
                                    <option value="">-- Select Template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('discount_template_id') == $template->id ? 'selected' : '' }}
                                            data-scope="{{ $template->scope }}"
                                            data-type="{{ $template->type }}"
                                            data-value="{{ $template->value }}">
                                            {{ $template->name }} ({{ $template->type === 'percentage' ? $template->value . '%' : 'Ksh ' . number_format($template->value, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('discount_template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Student <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
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

                            <div class="col-md-4">
                                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <select name="academic_year_id" class="form-select @error('academic_year_id') is-invalid @enderror">
                                    <option value="">-- Select Year --</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ old('academic_year_id', $currentYear->id ?? '') == $year->id ? 'selected' : '' }}>
                                            {{ $year->year }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('academic_year_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Year <span class="text-danger">*</span></label>
                                <input type="number" 
                                       name="year" 
                                       class="form-control @error('year') is-invalid @enderror" 
                                       value="{{ old('year', $currentYear->year ?? date('Y')) }}" 
                                       required>
                                @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Term <span class="text-danger">*</span></label>
                                <select name="term" class="form-select @error('term') is-invalid @enderror" required>
                                    <option value="1" {{ old('term') == 1 ? 'selected' : '' }}>Term 1</option>
                                    <option value="2" {{ old('term') == 2 ? 'selected' : '' }}>Term 2</option>
                                    <option value="3" {{ old('term') == 3 ? 'selected' : '' }}>Term 3</option>
                                </select>
                                @error('term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12" id="votehead_selector" style="display: none;">
                                <label class="form-label">Voteheads <span class="text-danger">*</span></label>
                                <select name="votehead_ids[]" class="form-select @error('votehead_ids') is-invalid @enderror" multiple size="5">
                                    @foreach($voteheads as $votehead)
                                        <option value="{{ $votehead->id }}" {{ in_array($votehead->id, old('votehead_ids', [])) ? 'selected' : '' }}>
                                            {{ $votehead->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple voteheads</small>
                                @error('votehead_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" 
                                       name="start_date" 
                                       class="form-control @error('start_date') is-invalid @enderror" 
                                       value="{{ old('start_date', date('Y-m-d')) }}">
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" 
                                       name="end_date" 
                                       class="form-control @error('end_date') is-invalid @enderror" 
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
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Template Info</h5>
                    </div>
                    <div class="card-body" id="template_info">
                        <p class="text-muted">Select a template to view details</p>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Allocate Discount
                    </button>
                    <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="btn btn-success">
                        <i class="bi bi-people"></i> Bulk Allocate Sibling Discounts
                    </a>
                    <a href="{{ route('finance.discounts.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
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
            const templateName = selectedOption.text;
            const valueDisplay = type === 'percentage' ? value + '%' : 'Ksh ' + parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            templateInfo.innerHTML = `
                <p><strong>Template:</strong> ${templateName}</p>
                <p><strong>Scope:</strong> ${scope.charAt(0).toUpperCase() + scope.slice(1)}</p>
                <p><strong>Value:</strong> ${valueDisplay}</p>
            `;
        } else {
            templateInfo.innerHTML = '<p class="text-muted">Select a template to view details</p>';
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

