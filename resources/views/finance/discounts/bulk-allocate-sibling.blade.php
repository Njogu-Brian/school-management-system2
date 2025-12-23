@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Bulk Allocate Sibling Discounts',
        'icon' => 'bi bi-people',
        'subtitle' => 'Automatically allocate sibling discounts to all eligible families',
        'actions' => '<a href="' . route('finance.discounts.allocations.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Allocations</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <i class="bi bi-info-circle me-2"></i> Bulk Allocation Details
                </div>
                <div class="finance-card-body">
                    <div class="alert alert-info" id="template_info_alert">
                        <i class="bi bi-info-circle"></i> 
                        <strong>How it works:</strong> The system will automatically find all families with 2 or more children and apply sibling discounts based on the selected template settings.
                        <div id="template_rules_display" class="mt-2">
                            <p class="mb-0"><small>Select a template to see the discount rules.</small></p>
                        </div>
                        <p class="mb-0 mt-2"><small>Children are sorted by date of birth (youngest first). The oldest child does not receive a discount.</small></p>
                    </div>

                    <form action="{{ route('finance.discounts.bulk-allocate-sibling.store') }}" method="POST" onsubmit="return confirm('This will allocate discounts to all eligible families. Continue?');">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="finance-form-label">Sibling Discount Template <span class="text-danger">*</span></label>
                                <select name="discount_template_id" class="finance-form-select @error('discount_template_id') is-invalid @enderror" required>
                                    <option value="">-- Select Template --</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" {{ old('discount_template_id') == $template->id ? 'selected' : '' }}
                                            data-type="{{ $template->type }}"
                                            data-value="{{ $template->value }}"
                                            data-scope="{{ $template->scope }}"
                                            data-sibling-rules="{{ json_encode($template->sibling_rules) }}"
                                            data-votehead-ids="{{ json_encode($template->votehead_ids) }}">
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
                                <small class="text-muted">Only sibling discount templates are shown</small>
                            </div>

                            <div class="col-md-12" id="template_details" style="display: none;">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h6 class="card-title">Template Details</h6>
                                        <div id="template_details_content"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="finance-form-label">Academic Year</label>
                                <select name="academic_year_id" class="finance-form-select @error('academic_year_id') is-invalid @enderror">
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
                                <label class="finance-form-label">Year <span class="text-danger">*</span></label>
                                <input type="number" 
                                       name="year" 
                                       class="finance-form-control @error('year') is-invalid @enderror" 
                                       value="{{ old('year', $currentYear->year ?? date('Y')) }}" 
                                       required>
                                @error('year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="finance-form-label">Term <span class="text-danger">*</span></label>
                                <select name="term" class="finance-form-select @error('term') is-invalid @enderror" required>
                                    <option value="1" {{ old('term') == 1 ? 'selected' : '' }}>Term 1</option>
                                    <option value="2" {{ old('term') == 2 ? 'selected' : '' }}>Term 2</option>
                                    <option value="3" {{ old('term') == 3 ? 'selected' : '' }}>Term 3</option>
                                </select>
                                @error('term')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-people-fill"></i> Allocate to All Families
                            </button>
                            <a href="{{ route('finance.discounts.index') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header secondary">
                    <i class="bi bi-bar-chart me-2"></i> Statistics
                </div>
                <div class="finance-card-body">
                    @php
                        $familiesWithSiblings = \App\Models\Family::has('students', '>=', 2)->count();
                        $totalStudents = \App\Models\Student::where('status', 'active')->whereNotNull('family_id')->count();
                    @endphp
                    <p class="mb-2">
                        <strong>Families with 2+ children:</strong> 
                        <span class="badge bg-primary">{{ $familiesWithSiblings }}</span>
                    </p>
                    <p class="mb-2">
                        <strong>Total active students:</strong> 
                        <span class="badge bg-info">{{ $totalStudents }}</span>
                    </p>
                    <hr>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        The system will process all families automatically and skip any that already have allocations for the selected term/year.
                    </small>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const templateSelect = document.querySelector('select[name="discount_template_id"]');
    const templateDetails = document.getElementById('template_details');
    const templateDetailsContent = document.getElementById('template_details_content');
    const templateRulesDisplay = document.getElementById('template_rules_display');

    templateSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            const templateName = selectedOption.text;
            const templateData = selectedOption.dataset;
            
            // Show template details
            templateDetails.style.display = 'block';
            
            // Build details HTML
            let detailsHtml = `<p><strong>Template:</strong> ${templateName}</p>`;
            
            // Show sibling rules if available
            if (templateData.siblingRules) {
                try {
                    const rules = JSON.parse(templateData.siblingRules);
                    let rulesHtml = '<p><strong>Sibling Discount Rules:</strong></p><ul>';
                    Object.keys(rules).sort((a, b) => parseInt(a) - parseInt(b)).forEach(position => {
                        rulesHtml += `<li><strong>${position}${getOrdinalSuffix(parseInt(position))} child:</strong> ${rules[position]}%</li>`;
                    });
                    rulesHtml += '</ul>';
                    detailsHtml += rulesHtml;
                    
                    // Update alert
                    let alertRulesHtml = '<ul class="mb-0 mt-2">';
                    Object.keys(rules).sort((a, b) => parseInt(a) - parseInt(b)).forEach(position => {
                        alertRulesHtml += `<li><strong>${position}${getOrdinalSuffix(parseInt(position))} child:</strong> ${rules[position]}% discount</li>`;
                    });
                    alertRulesHtml += '</ul>';
                    templateRulesDisplay.innerHTML = alertRulesHtml;
                } catch (e) {
                    templateRulesDisplay.innerHTML = '<p class="mb-0"><small>Using base value from template.</small></p>';
                }
            } else {
                templateRulesDisplay.innerHTML = '<p class="mb-0"><small>Using base value from template (will multiply by position).</small></p>';
            }
            
            // Show voteheads if scope is votehead
            if (templateData.scope === 'votehead' && templateData.voteheadIds) {
                try {
                    const voteheadIds = JSON.parse(templateData.voteheadIds);
                    if (voteheadIds.length > 0) {
                        detailsHtml += `<p><strong>Applies to:</strong> Specific voteheads (${voteheadIds.length} selected)</p>`;
                    } else {
                        detailsHtml += `<p><strong>Applies to:</strong> All voteheads</p>`;
                    }
                } catch (e) {
                    detailsHtml += `<p><strong>Applies to:</strong> All voteheads</p>`;
                }
            } else {
                detailsHtml += `<p><strong>Scope:</strong> ${templateData.scope ? templateData.scope.charAt(0).toUpperCase() + templateData.scope.slice(1) : 'N/A'}</p>`;
            }
            
            templateDetailsContent.innerHTML = detailsHtml;
        } else {
            templateDetails.style.display = 'none';
            templateRulesDisplay.innerHTML = '<p class="mb-0"><small>Select a template to see the discount rules.</small></p>';
        }
    });

    function getOrdinalSuffix(n) {
        const s = ["th", "st", "nd", "rd"];
        const v = n % 100;
        return s[(v - 20) % 10] || s[v] || s[0];
    }

    // Trigger on page load if template is pre-selected
    if (templateSelect.value) {
        templateSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection

