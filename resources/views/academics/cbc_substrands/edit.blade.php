@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit CBC Substrand</h1>
        <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.cbc-substrands.update', $cbc_substrand) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Strand <span class="text-danger">*</span></label>
                        <select name="strand_id" class="form-select @error('strand_id') is-invalid @enderror" required>
                            <option value="">Select Strand</option>
                            @foreach($strands as $strand)
                                <option value="{{ $strand->id }}" 
                                        {{ old('strand_id', $cbc_substrand->strand_id) == $strand->id ? 'selected' : '' }}>
                                    {{ $strand->code }} - {{ $strand->name }} ({{ $strand->level }})
                                </option>
                            @endforeach
                        </select>
                        @error('strand_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                               value="{{ old('code', $cbc_substrand->code) }}" required maxlength="20">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $cbc_substrand->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $cbc_substrand->description) }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Learning Outcomes</label>
                        <textarea name="learning_outcomes" class="form-control" rows="4">@if(is_array(old('learning_outcomes', $cbc_substrand->learning_outcomes))){{ implode("\n", old('learning_outcomes', $cbc_substrand->learning_outcomes)) }}@else{{ old('learning_outcomes', $cbc_substrand->learning_outcomes) }}@endif</textarea>
                        <small class="text-muted">Enter one outcome per line.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Key Inquiry Questions</label>
                        <textarea name="key_inquiry_questions" class="form-control" rows="4">@if(is_array(old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions))){{ implode("\n", old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions)) }}@else{{ old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions) }}@endif</textarea>
                        <small class="text-muted">Enter one question per line.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Core Competencies</label>
                        <textarea name="core_competencies" class="form-control" rows="3">@if(is_array(old('core_competencies', $cbc_substrand->core_competencies))){{ implode("\n", old('core_competencies', $cbc_substrand->core_competencies)) }}@else{{ old('core_competencies', $cbc_substrand->core_competencies) }}@endif</textarea>
                        <small class="text-muted">Enter one competency per line.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Values</label>
                        <textarea name="values" class="form-control" rows="3">@if(is_array(old('values', $cbc_substrand->values))){{ implode("\n", old('values', $cbc_substrand->values)) }}@else{{ old('values', $cbc_substrand->values) }}@endif</textarea>
                        <small class="text-muted">Enter one value per line.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">PCLC (Parent, Community, Learner)</label>
                        <textarea name="pclc" class="form-control" rows="3">@if(is_array(old('pclc', $cbc_substrand->pclc))){{ implode("\n", old('pclc', $cbc_substrand->pclc)) }}@else{{ old('pclc', $cbc_substrand->pclc) }}@endif</textarea>
                        <small class="text-muted">Enter one item per line.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Suggested Lessons</label>
                        <input type="number" name="suggested_lessons" class="form-control" 
                               value="{{ old('suggested_lessons', $cbc_substrand->suggested_lessons) }}" min="1" max="20">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" 
                               value="{{ old('display_order', $cbc_substrand->display_order) }}" min="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $cbc_substrand->is_active) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !old('is_active', $cbc_substrand->is_active) ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Substrand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Convert textarea inputs to arrays when form is submitted
document.querySelector('form').addEventListener('submit', function(e) {
    const textareas = ['learning_outcomes', 'key_inquiry_questions', 'core_competencies', 'values', 'pclc'];
    
    textareas.forEach(function(fieldName) {
        const textarea = document.querySelector(`textarea[name="${fieldName}"]`);
        if (textarea && textarea.value.trim()) {
            const lines = textarea.value.split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);
            
            // Create hidden inputs for each line
            textarea.value = ''; // Clear the textarea
            lines.forEach(function(line, index) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `${fieldName}[${index}]`;
                input.value = line;
                textarea.parentNode.appendChild(input);
            });
        }
    });
});
</script>
@endsection

