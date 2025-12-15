@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0">
                <i class="bi bi-plus-circle"></i> Create Discount Template
            </h3>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <form action="{{ route('finance.discounts.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Discount Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-select @error('discount_type') is-invalid @enderror" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="sibling" {{ old('discount_type') == 'sibling' ? 'selected' : '' }}>Sibling Discount</option>
                                    <option value="referral" {{ old('discount_type') == 'referral' ? 'selected' : '' }}>Referral Discount</option>
                                    <option value="early_repayment" {{ old('discount_type') == 'early_repayment' ? 'selected' : '' }}>Early Repayment</option>
                                    <option value="transport" {{ old('discount_type') == 'transport' ? 'selected' : '' }}>Transport Discount</option>
                                    <option value="manual" {{ old('discount_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                                    <option value="other" {{ old('discount_type') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('discount_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Scope <span class="text-danger">*</span></label>
                                <select name="scope" id="scope" class="form-select @error('scope') is-invalid @enderror" required>
                                    <option value="">-- Select Scope --</option>
                                    <option value="votehead" {{ old('scope') == 'votehead' ? 'selected' : '' }}>Votehead</option>
                                    <option value="invoice" {{ old('scope') == 'invoice' ? 'selected' : '' }}>Invoice</option>
                                    <option value="student" {{ old('scope') == 'student' ? 'selected' : '' }}>Student</option>
                                    <option value="family" {{ old('scope') == 'family' ? 'selected' : '' }}>Family</option>
                                </select>
                                @error('scope')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Discount Amount Type <span class="text-danger">*</span></label>
                                <select name="type" id="discount_amount_type" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="percentage" {{ old('type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed_amount" {{ old('type') == 'fixed_amount' ? 'selected' : '' }}>Fixed Amount (Ksh)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" id="value_label">Discount Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="value" 
                                           step="0.01" 
                                           min="0" 
                                           class="form-control @error('value') is-invalid @enderror" 
                                           value="{{ old('value') }}" 
                                           required>
                                    <span class="input-group-text" id="value_suffix">%</span>
                                </div>
                                @error('value')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Frequency <span class="text-danger">*</span></label>
                                <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                                    <option value="termly" {{ old('frequency') == 'termly' ? 'selected' : '' }}>Termly</option>
                                    <option value="yearly" {{ old('frequency') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                    <option value="once" {{ old('frequency') == 'once' ? 'selected' : '' }}>Once</option>
                                    <option value="manual" {{ old('frequency') == 'manual' ? 'selected' : '' }}>Manual</option>
                                </select>
                                @error('frequency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Sibling Discount Rules (only shown for sibling discount type) -->
                            <div class="col-md-12" id="sibling_rules_section" style="display: none;">
                                <label class="form-label">Sibling Discount Rules</label>
                                <div class="alert alert-info">
                                    <small>Define discount percentage for each child position. Leave blank to use base value × position.</small>
                                </div>
                                <div class="row g-2" id="sibling_rules_container">
                                    <div class="col-md-3">
                                        <label class="form-label small">2nd Child (%)</label>
                                        <input type="number" name="sibling_rules[2]" class="form-control form-control-sm" 
                                               value="{{ old('sibling_rules.2') }}" step="0.01" min="0" max="100">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">3rd Child (%)</label>
                                        <input type="number" name="sibling_rules[3]" class="form-control form-control-sm" 
                                               value="{{ old('sibling_rules.3') }}" step="0.01" min="0" max="100">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">4th Child (%)</label>
                                        <input type="number" name="sibling_rules[4]" class="form-control form-control-sm" 
                                               value="{{ old('sibling_rules.4') }}" step="0.01" min="0" max="100">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">5th Child (%)</label>
                                        <input type="number" name="sibling_rules[5]" class="form-control form-control-sm" 
                                               value="{{ old('sibling_rules.5') }}" step="0.01" min="0" max="100">
                                    </div>
                                </div>
                                <small class="text-muted">Example: 2nd child = 5%, 3rd child = 10%, 4th child = 15%</small>
                            </div>

                            <!-- Votehead Selection (only shown for votehead scope) -->
                            <div class="col-md-12" id="votehead_selection_section" style="display: none;">
                                <label class="form-label">Apply to Voteheads</label>
                                <select name="votehead_ids[]" class="form-select @error('votehead_ids') is-invalid @enderror" multiple size="5">
                                    @foreach(\App\Models\Votehead::orderBy('name')->get() as $votehead)
                                        <option value="{{ $votehead->id }}" {{ in_array($votehead->id, old('votehead_ids', [])) ? 'selected' : '' }}>
                                            {{ $votehead->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple voteheads. Leave empty to apply to all voteheads.</small>
                                @error('votehead_ids')
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
                                <small class="text-muted">Leave blank for no expiry</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Template Name <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" 
                                       placeholder="e.g., Sibling Discount 10%" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <input type="text" 
                                       name="reason" 
                                       class="form-control @error('reason') is-invalid @enderror" 
                                       value="{{ old('reason') }}" 
                                       placeholder="e.g., Sibling discount for 3 children" 
                                       required>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" 
                                          class="form-control @error('description') is-invalid @enderror" 
                                          rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="requires_approval" 
                                           value="1" 
                                           id="requires_approval"
                                           {{ old('requires_approval', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_approval">
                                        Requires approval when allocated
                                    </label>
                                </div>
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
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <small>This creates a discount template. To assign it to students, use the "Allocate Discount" option after creation.</small>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Template
                    </button>
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
    const discountType = document.getElementById('discount_amount_type');
    const valueSuffix = document.getElementById('value_suffix');
    const discountTypeSelect = document.querySelector('select[name="discount_type"]');
    const scopeSelect = document.getElementById('scope');
    const siblingRulesSection = document.getElementById('sibling_rules_section');
    const voteheadSelectionSection = document.getElementById('votehead_selection_section');

    // Handle discount type change
    discountType.addEventListener('change', function() {
        valueSuffix.textContent = this.value === 'percentage' ? '%' : 'Ksh';
    });

    // Handle discount type (sibling, referral, etc.) change
    discountTypeSelect.addEventListener('change', function() {
        if (this.value === 'sibling') {
            siblingRulesSection.style.display = 'block';
        } else {
            siblingRulesSection.style.display = 'none';
        }
    });

    // Handle scope change
    scopeSelect.addEventListener('change', function() {
        if (this.value === 'votehead') {
            voteheadSelectionSection.style.display = 'block';
        } else {
            voteheadSelectionSection.style.display = 'none';
        }
    });

    // Trigger on page load
    discountType.dispatchEvent(new Event('change'));
    discountTypeSelect.dispatchEvent(new Event('change'));
    scopeSelect.dispatchEvent(new Event('change'));
});
</script>
@endpush
@endsection

