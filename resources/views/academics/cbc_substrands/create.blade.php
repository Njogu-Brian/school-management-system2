@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create CBC Substrand</h1>
        <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('academics.cbc-substrands.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Strand <span class="text-danger">*</span></label>
                        <select name="strand_id" class="form-select @error('strand_id') is-invalid @enderror" required>
                            <option value="">Select Strand</option>
                            @foreach($strands as $strand)
                                <option value="{{ $strand->id }}" {{ old('strand_id') == $strand->id ? 'selected' : '' }}>
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
                               value="{{ old('code') }}" required maxlength="20" 
                               placeholder="e.g., ENG.G1.S1.1">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Unique code for this substrand</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="Brief description of the substrand">{{ old('description') }}</textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Learning Outcomes</label>
                        <textarea name="learning_outcomes" class="form-control" rows="4" 
                                  placeholder="Enter learning outcomes, one per line">{{ old('learning_outcomes') }}</textarea>
                        <small class="text-muted">Enter one outcome per line. They will be stored as an array.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Key Inquiry Questions</label>
                        <textarea name="key_inquiry_questions" class="form-control" rows="4" 
                                  placeholder="Enter key inquiry questions, one per line">{{ old('key_inquiry_questions') }}</textarea>
                        <small class="text-muted">Enter one question per line. They will be stored as an array.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Core Competencies</label>
                        <textarea name="core_competencies" class="form-control" rows="3" 
                                  placeholder="Enter core competencies, one per line">{{ old('core_competencies') }}</textarea>
                        <small class="text-muted">Enter one competency per line.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Values</label>
                        <textarea name="values" class="form-control" rows="3" 
                                  placeholder="Enter values, one per line">{{ old('values') }}</textarea>
                        <small class="text-muted">Enter one value per line.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">PCLC (Parent, Community, Learner)</label>
                        <textarea name="pclc" class="form-control" rows="3" 
                                  placeholder="Enter PCLC items, one per line">{{ old('pclc') }}</textarea>
                        <small class="text-muted">Enter one item per line.</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Suggested Lessons</label>
                        <input type="number" name="suggested_lessons" class="form-control" 
                               value="{{ old('suggested_lessons', 3) }}" min="1" max="20">
                        <small class="text-muted">Number of suggested lessons for this substrand</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" 
                               value="{{ old('display_order', 0) }}" min="0">
                        <small class="text-muted">Order in which this substrand appears</small>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Substrand</button>
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

