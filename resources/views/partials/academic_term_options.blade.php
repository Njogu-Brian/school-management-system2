@foreach(($terms ?? \App\Support\AcademicContext::allTermsForSelect()) as $t)
  <option value="{{ $t->id }}"
          data-academic-year-id="{{ $t->academic_year_id }}"
          @selected(isset($selected) ? (int) $selected === (int) $t->id : (int) ($selectedTermId ?? old('term_id', request('term_id'))) === (int) $t->id)>
    @if($t->relationLoaded('academicYear') && $t->academicYear)
      {{ $t->academicYear->year }} · {{ $t->name }}
    @else
      {{ $t->name }}
    @endif
  </option>
@endforeach
