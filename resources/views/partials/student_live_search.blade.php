@php
  // Props:
  // $hiddenInputId (required): id for hidden input to set selected student id
  // $displayInputId (required): id for visible text input
  // $resultsId (required): id for results container
  // $enableButtonId (optional): id of a button to enable when a student is selected
  // $placeholder (optional): placeholder text
  // $initialLabel (optional): prefilled display value
  // $inputClass (optional): additional CSS classes for the input field
  $hiddenInputId = $hiddenInputId ?? 'selectedStudentId';
  $displayInputId = $displayInputId ?? 'studentLiveSearch';
  $resultsId = $resultsId ?? 'studentLiveResults';
  $enableButtonId = $enableButtonId ?? null;
  $placeholder = $placeholder ?? 'Type name or admission #';
  $initialLabel = $initialLabel ?? '';
  $inputClass = $inputClass ?? 'form-control';
@endphp

<div class="student-live-search"
     data-hidden="{{ $hiddenInputId }}"
     data-display="{{ $displayInputId }}"
     data-results="{{ $resultsId }}"
     data-enable="{{ $enableButtonId }}"
     data-search-url="{{ $searchUrl ?? '' }}">
    <input type="hidden" id="{{ $hiddenInputId }}" name="student_id" value="{{ old('student_id', request('student_id')) }}">
    <input type="text" id="{{ $displayInputId }}" class="{{ $inputClass }}"
           value="{{ $initialLabel }}"
           placeholder="{{ $placeholder }}">
    <div id="{{ $resultsId }}" class="list-group shadow-sm mt-1 d-none" style="max-height: 220px; overflow-y: auto;"></div>
    <small class="text-muted">Start typing; results appear below automatically.</small>
</div>

@pushOnce('scripts')
<script src="{{ asset('js/student-live-search.js') }}"></script>
@endPushOnce
