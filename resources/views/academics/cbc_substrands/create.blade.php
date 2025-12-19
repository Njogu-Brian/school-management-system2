@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · CBC Substrands</div>
        <h1 class="mb-1">Create CBC Substrand</h1>
        <p class="text-muted mb-0">Define substrand details and learning outcomes.</p>
      </div>
      <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.cbc-substrands.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Strand <span class="text-danger">*</span></label>
              <select name="strand_id" class="form-select @error('strand_id') is-invalid @enderror" required>
                <option value="">Select Strand</option>
                @foreach($strands as $strand)
                  <option value="{{ $strand->id }}" {{ old('strand_id') == $strand->id ? 'selected' : '' }}>{{ $strand->code }} - {{ $strand->name }} ({{ $strand->level }})</option>
                @endforeach
              </select>
              @error('strand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="20" placeholder="e.g., ENG.G1.S1.1">
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <small class="text-muted">Unique code for this substrand</small>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the substrand">{{ old('description') }}</textarea>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Learning Outcomes</label>
              <textarea name="learning_outcomes" class="form-control" rows="4" placeholder="Enter learning outcomes, one per line">{{ old('learning_outcomes') }}</textarea>
              <small class="text-muted">One outcome per line; stored as an array.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Key Inquiry Questions</label>
              <textarea name="key_inquiry_questions" class="form-control" rows="4" placeholder="Enter key inquiry questions, one per line">{{ old('key_inquiry_questions') }}</textarea>
              <small class="text-muted">One question per line; stored as an array.</small>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Core Competencies</label>
              <textarea name="core_competencies" class="form-control" rows="3" placeholder="One per line">{{ old('core_competencies') }}</textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Values</label>
              <textarea name="values" class="form-control" rows="3" placeholder="One per line">{{ old('values') }}</textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">PCLC (Parent, Community, Learner)</label>
              <textarea name="pclc" class="form-control" rows="3" placeholder="One per line">{{ old('pclc') }}</textarea>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Suggested Lessons</label>
              <input type="number" name="suggested_lessons" class="form-control" value="{{ old('suggested_lessons', 3) }}" min="1" max="20">
              <small class="text-muted">Number of suggested lessons</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="{{ old('display_order', 0) }}" min="0">
            </div>
          </div>

          <div class="mt-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Create Substrand</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.querySelector('form').addEventListener('submit', function() {
  ['learning_outcomes','key_inquiry_questions','core_competencies','values','pclc'].forEach(function(field){
    const textarea=document.querySelector(`textarea[name="${field}"]`);
    if(textarea && textarea.value.trim()){
      const lines=textarea.value.split('\n').map(l=>l.trim()).filter(l=>l.length>0);
      textarea.value='';
      lines.forEach((line,idx)=>{
        const input=document.createElement('input');
        input.type='hidden';input.name=`${field}[${idx}]`;input.value=line;textarea.parentNode.appendChild(input);
      });
    }
  });
});
</script>
@endpush
@endsection
