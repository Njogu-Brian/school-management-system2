@php
  $grade = $grade ?? null;
  $wide = $wide ?? false;
@endphp
@if(!empty($grade))
  <span class="cbc-grade-badge cbc-grade--{{ $grade['tier'] }}{{ $wide ? ' cbc-grade-badge--wide' : '' }}"
        title="{{ $grade['label'] }} ({{ number_format($grade['percent'], 1) }}%)">
    {{ $wide ? $grade['label'] : $grade['short'] }}
  </span>
@endif
