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
        <h1 class="mb-1">Edit CBC Substrand</h1>
        <p class="text-muted mb-0">Update details, outcomes, and status.</p>
      </div>
      <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.cbc-substrands.update', $cbc_substrand) }}" method="POST">
          @csrf @method('PUT')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Strand <span class="text-danger">*</span></label>
              <select name="strand_id" class="form-select @error('strand_id') is-invalid @enderror" required>
                <option value="">Select Strand</option>
                @foreach($strands as $strand)
                  <option value="{{ $strand->id }}" {{ old('strand_id', $cbc_substrand->strand_id) == $strand->id ? 'selected' : '' }}>{{ $strand->code }} - {{ $strand->name }} ({{ $strand->level }})</option>
                @endforeach
              </select>
              @error('strand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $cbc_substrand->code) }}" required maxlength="20">
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cbc_substrand->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mt-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $cbc_substrand->description) }}</textarea>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Learning Outcomes</label>
              <textarea name="learning_outcomes" class="form-control" rows="4">@if(is_array(old('learning_outcomes', $cbc_substrand->learning_outcomes))){{ implode("\n", old('learning_outcomes', $cbc_substrand->learning_outcomes)) }}@else{{ old('learning_outcomes', $cbc_substrand->learning_outcomes) }}@endif</textarea>
              <small class="text-muted">One outcome per line.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Key Inquiry Questions</label>
              <textarea name="key_inquiry_questions" class="form-control" rows="4">@if(is_array(old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions))){{ implode("\n", old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions)) }}@else{{ old('key_inquiry_questions', $cbc_substrand->key_inquiry_questions) }}@endif</textarea>
              <small class="text-muted">One question per line.</small>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Core Competencies</label>
              <textarea name="core_competencies" class="form-control" rows="3">@if(is_array(old('core_competencies', $cbc_substrand->core_competencies))){{ implode("\n", old('core_competencies', $cbc_substrand->core_competencies)) }}@else{{ old('core_competencies', $cbc_substrand->core_competencies) }}@endif</textarea>
              <small class="text-muted">One per line.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label">Values</label>
              <textarea name="values" class="form-control" rows="3">@if(is_array(old('values', $cbc_substrand->values))){{ implode("\n", old('values', $cbc_substrand->values)) }}@else{{ old('values', $cbc_substrand->values) }}@endif</textarea>
              <small class="text-muted">One per line.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label">PCLC (Parent, Community, Learner)</label>
              <textarea name="pclc" class="form-control" rows="3">@if(is_array(old('pclc', $cbc_substrand->pclc))){{ implode("\n", old('pclc', $cbc_substrand->pclc)) }}@else{{ old('pclc', $cbc_substrand->pclc) }}@endif</textarea>
              <small class="text-muted">One per line.</small>
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label">Suggested Lessons</label>
              <input type="number" name="suggested_lessons" class="form-control" value="{{ old('suggested_lessons', $cbc_substrand->suggested_lessons) }}" min="1" max="20">
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="{{ old('display_order', $cbc_substrand->display_order) }}" min="0">
            </div>
          </div>

          <div class="row g-3 mt-1">
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select name="is_active" class="form-select">
                <option value="1" {{ old('is_active', $cbc_substrand->is_active) ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !old('is_active', $cbc_substrand->is_active) ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-3">
            <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-ghost-strong">Cancel</a>
            <button type="submit" class="btn btn-settings-primary">Update Substrand</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.querySelector('form').addEventListener('submit', function(){
  ['learning_outcomes','key_inquiry_questions','core_competencies','values','pclc'].forEach(function(field){
    const textarea=document.querySelector(`textarea[name="${field}"]`);
    if(textarea && textarea.value.trim()){
      const lines=textarea.value.split('\n').map(l=>l.trim()).filter(l=>l.length>0);
      textarea.value='';
      lines.forEach((line,idx)=>{const input=document.createElement('input');input.type='hidden';input.name=`${field}[${idx}]`;input.value=line;textarea.parentNode.appendChild(input);});
    }
  });
});
</script>
@endpush
@endsection
