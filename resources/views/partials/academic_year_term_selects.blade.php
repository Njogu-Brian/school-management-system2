@php
  use App\Support\AcademicContext;

  $years = $years ?? $academicYears ?? AcademicContext::years();
  $terms = $terms ?? AcademicContext::allTermsForSelect();
  $yearName = $yearName ?? 'academic_year_id';
  $termName = $termName ?? 'term_id';
  $yearSelectId = $yearSelectId ?? $yearName;
  $termSelectId = $termSelectId ?? $termName;
  $selectedYearId = $selectedYearId ?? AcademicContext::resolveYearId(null);
  $selectedTermId = $selectedTermId ?? AcademicContext::resolveTermId($selectedYearId, null);
  $allowEmptyYear = $allowEmptyYear ?? false;
  $allowEmptyTerm = $allowEmptyTerm ?? false;
  $yearRequired = $yearRequired ?? false;
  $termRequired = $termRequired ?? false;
  $yearCol = $yearCol ?? 'col-md-3';
  $termCol = $termCol ?? 'col-md-3';
  $yearLabel = $yearLabel ?? 'Academic Year';
  $termLabel = $termLabel ?? 'Term';
  $yearEmptyLabel = $yearEmptyLabel ?? 'All Years';
  $termEmptyLabel = $termEmptyLabel ?? 'All Terms';
  $showYearPrefixInTerm = $showYearPrefixInTerm ?? true;
@endphp

<div class="{{ $yearCol }}">
  <label class="form-label">{{ $yearLabel }}@if($yearRequired) <span class="text-danger">*</span>@endif</label>
  <select name="{{ $yearName }}" id="{{ $yearSelectId }}" class="form-select academic-year-select" @if($yearRequired) required @endif>
    @if($allowEmptyYear)
      <option value="">{{ $yearEmptyLabel }}</option>
    @endif
    @foreach($years as $y)
      <option value="{{ $y->id }}" @selected((int) $selectedYearId === (int) $y->id)>{{ $y->year }}</option>
    @endforeach
  </select>
</div>
<div class="{{ $termCol }}">
  <label class="form-label">{{ $termLabel }}@if($termRequired) <span class="text-danger">*</span>@endif</label>
  <select name="{{ $termName }}" id="{{ $termSelectId }}" class="form-select academic-term-select" @if($termRequired) required @endif>
    @if($allowEmptyTerm)
      <option value="">{{ $termEmptyLabel }}</option>
    @endif
    @foreach($terms as $t)
      <option value="{{ $t->id }}"
              data-academic-year-id="{{ $t->academic_year_id }}"
              @selected((int) $selectedTermId === (int) $t->id)>
        @if($showYearPrefixInTerm && $t->academicYear)
          {{ $t->academicYear->year }} · {{ $t->name }}
        @else
          {{ $t->name }}
        @endif
      </option>
    @endforeach
  </select>
</div>

@push('scripts')
<script>
(function () {
  if (typeof window.initAcademicYearTermSelects === 'function') {
    window.initAcademicYearTermSelects(document.getElementById(@json($yearSelectId)), document.getElementById(@json($termSelectId)));
  }
})();
</script>
@endpush
