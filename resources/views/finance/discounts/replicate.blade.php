@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Replicate Discounts',
        'icon' => 'bi bi-copy',
        'subtitle' => 'Replicate discount allocations across terms and classes'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-info-circle me-2"></i> Replication Information
        </div>
        <div class="finance-card-body">
            <form method="POST" action="{{ route('finance.discounts.replicate') }}">
                @csrf
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="finance-form-label">Discount Template <span class="text-danger">*</span></label>
                        <select name="template_id" class="finance-form-select @error('template_id') is-invalid @enderror" required>
                            <option value="">-- Select Template --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('template_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="finance-form-label">Source Year <span class="text-danger">*</span></label>
                        <input type="number" 
                               name="source_year" 
                               class="finance-form-control @error('source_year') is-invalid @enderror" 
                               value="{{ old('source_year', $currentYear->year ?? date('Y')) }}" 
                               required>
                        @error('source_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="finance-form-label">Source Term <span class="text-danger">*</span></label>
                        <select name="source_term" class="finance-form-select @error('source_term') is-invalid @enderror" required>
                            <option value="1" {{ old('source_term') == '1' ? 'selected' : '' }}>Term 1</option>
                            <option value="2" {{ old('source_term') == '2' ? 'selected' : '' }}>Term 2</option>
                            <option value="3" {{ old('source_term') == '3' ? 'selected' : '' }}>Term 3</option>
                        </select>
                        @error('source_term')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <hr>
                        <h5>Target Years <span class="text-danger">*</span></h5>
                        <div class="row">
                            @foreach($academicYears as $year)
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="target_years[]" 
                                           value="{{ $year->year }}" 
                                           id="year_{{ $year->year }}"
                                           {{ in_array($year->year, old('target_years', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="year_{{ $year->year }}">
                                        {{ $year->name }} ({{ $year->year }})
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @error('target_years')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <h5>Target Terms <span class="text-danger">*</span></h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="target_terms[]" 
                                           value="1" 
                                           id="term_1"
                                           {{ in_array('1', old('target_terms', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="term_1">Term 1</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="target_terms[]" 
                                           value="2" 
                                           id="term_2"
                                           {{ in_array('2', old('target_terms', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="term_2">Term 2</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="target_terms[]" 
                                           value="3" 
                                           id="term_3"
                                           {{ in_array('3', old('target_terms', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="term_3">Term 3</label>
                                </div>
                            </div>
                        </div>
                        @error('target_terms')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <h5>Target Classes (Optional)</h5>
                        <small class="text-muted">Leave empty to replicate to all classes. Select specific classes to limit replication.</small>
                        <div class="row mt-2">
                            @foreach($classrooms as $classroom)
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="target_classrooms[]" 
                                           value="{{ $classroom->id }}" 
                                           id="class_{{ $classroom->id }}"
                                           {{ in_array($classroom->id, old('target_classrooms', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="class_{{ $classroom->id }}">
                                        {{ $classroom->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> This will replicate all approved discount allocations from the source term/year to the selected target terms/years. Existing allocations will be skipped.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-finance btn-finance-primary">
                                <i class="bi bi-check-circle"></i> Replicate Discounts
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

