@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Competency</h1>
        <a href="{{ route('academics.competencies.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.competencies.update', $competency) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="substrand_id" class="form-label">Substrand <span class="text-danger">*</span></label>
                    <select class="form-select @error('substrand_id') is-invalid @enderror" 
                            id="substrand_id" name="substrand_id" required>
                        <option value="">Select Substrand</option>
                        @if($competency->substrand)
                            <option value="{{ $competency->substrand->id }}" selected>
                                {{ $competency->substrand->strand->learningArea->name ?? '' }} - {{ $competency->substrand->strand->name ?? '' }} - {{ $competency->substrand->name }} ({{ $competency->substrand->code }})
                            </option>
                        @endif
                        @if(isset($substrands) && $substrands->count() > 0)
                            @foreach($substrands as $substrand)
                                @if($substrand->id != $competency->substrand_id)
                                <option value="{{ $substrand->id }}" {{ old('substrand_id') == $substrand->id ? 'selected' : '' }}>
                                    {{ $substrand->strand->learningArea->name ?? '' }} - {{ $substrand->strand->name ?? '' }} - {{ $substrand->name }} ({{ $substrand->code }})
                                </option>
                                @endif
                            @endforeach
                        @endif
                    </select>
                    @error('substrand_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Or filter by learning area below</small>
                </div>

                @if(isset($learningAreas) && $learningAreas->count() > 0)
                <div class="mb-3">
                    <label class="form-label">Filter by Learning Area</label>
                    <select class="form-select" id="learning_area_filter" onchange="loadSubstrands()">
                        <option value="">All Learning Areas</option>
                        @foreach($learningAreas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" 
                               id="code" name="code" value="{{ old('code', $competency->code) }}" required>
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $competency->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3">{{ old('description', $competency->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="competency_level" class="form-label">Competency Level</label>
                        <select class="form-select @error('competency_level') is-invalid @enderror" 
                                id="competency_level" name="competency_level">
                            <option value="">Select Level</option>
                            @foreach($competencyLevels as $key => $level)
                                <option value="{{ $level }}" 
                                        {{ old('competency_level', $competency->competency_level) == $level ? 'selected' : '' }}>
                                    {{ $level }}
                                </option>
                            @endforeach
                        </select>
                        @error('competency_level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control @error('display_order') is-invalid @enderror" 
                               id="display_order" name="display_order" 
                               value="{{ old('display_order', $competency->display_order) }}" min="0">
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Indicators</label>
                    <div id="indicators_container">
                        @php
                            $indicators = old('indicators', $competency->indicators ?? []);
                            if (empty($indicators)) {
                                $indicators = [''];
                            }
                        @endphp
                        @foreach($indicators as $indicator)
                        <div class="input-group mb-2">
                            <input type="text" name="indicators[]" class="form-control" 
                                   value="{{ is_array($indicator) ? ($indicator['text'] ?? '') : $indicator }}" 
                                   placeholder="Indicator">
                            <button type="button" class="btn btn-outline-danger" onclick="removeIndicator(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addIndicator()">
                        <i class="bi bi-plus"></i> Add Indicator
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Assessment Criteria</label>
                    <div id="criteria_container">
                        @php
                            $criteria = old('assessment_criteria', $competency->assessment_criteria ?? []);
                            if (empty($criteria)) {
                                $criteria = [''];
                            }
                        @endphp
                        @foreach($criteria as $criterion)
                        <div class="input-group mb-2">
                            <input type="text" name="assessment_criteria[]" class="form-control" 
                                   value="{{ is_array($criterion) ? ($criterion['text'] ?? '') : $criterion }}" 
                                   placeholder="Assessment criteria">
                            <button type="button" class="btn btn-outline-danger" onclick="removeCriterion(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCriterion()">
                        <i class="bi bi-plus"></i> Add Criterion
                    </button>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                               {{ old('is_active', $competency->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Active
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.competencies.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Competency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addIndicator() {
    const container = document.getElementById('indicators_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="indicators[]" class="form-control" placeholder="Indicator">
        <button type="button" class="btn btn-outline-danger" onclick="removeIndicator(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeIndicator(button) {
    button.closest('.input-group').remove();
}

function addCriterion() {
    const container = document.getElementById('criteria_container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `
        <input type="text" name="assessment_criteria[]" class="form-control" placeholder="Assessment criteria">
        <button type="button" class="btn btn-outline-danger" onclick="removeCriterion(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeCriterion(button) {
    button.closest('.input-group').remove();
}

function loadSubstrands() {
    const learningAreaId = document.getElementById('learning_area_filter').value;
    const substrandSelect = document.getElementById('substrand_id');
    
    // Get all substrands from the page
    const allOptions = Array.from(substrandSelect.options);
    const currentSelected = {{ $competency->substrand_id ?? 'null' }};
    
    if (!learningAreaId) {
        // Show all substrands
        substrandSelect.innerHTML = '';
        allOptions.forEach(option => {
            if (option.value === '' || option.value == currentSelected) {
                substrandSelect.appendChild(option.cloneNode(true));
            }
        });
        // Re-add other substrands
        @if(isset($substrands) && $substrands->count() > 0)
            @foreach($substrands as $substrand)
                @if($substrand->id != $competency->substrand_id)
                    if (!Array.from(substrandSelect.options).find(opt => opt.value == '{{ $substrand->id }}')) {
                        const option = document.createElement('option');
                        option.value = '{{ $substrand->id }}';
                        option.textContent = '{{ $substrand->strand->learningArea->name ?? '' }} - {{ $substrand->strand->name ?? '' }} - {{ $substrand->name }} ({{ $substrand->code }})';
                        substrandSelect.appendChild(option);
                    }
                @endif
            @endforeach
        @endif
        return;
    }

    // Filter substrands by learning area
    substrandSelect.innerHTML = '<option value="">Select Substrand</option>';
    
    @if(isset($substrands) && $substrands->count() > 0)
        const substrandsData = @json($substrands->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'strand_name' => $s->strand->name ?? '',
                'learning_area_name' => $s->strand->learningArea->name ?? '',
                'learning_area_id' => $s->strand->learningArea->id ?? null,
            ];
        }));
        
        substrandsData.forEach(item => {
            if (item.learning_area_id == learningAreaId || item.id == currentSelected) {
                const option = document.createElement('option');
                option.value = item.id;
                option.selected = item.id == currentSelected;
                option.textContent = `${item.learning_area_name || ''} - ${item.strand_name} - ${item.name} (${item.code})`;
                substrandSelect.appendChild(option);
            }
        });
    @endif
}
</script>
@endsection

