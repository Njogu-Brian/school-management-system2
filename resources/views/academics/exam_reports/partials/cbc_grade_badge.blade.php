@php
  $grade = $grade ?? null;
@endphp
@if(!empty($grade))
  <span class="cbc-grade-badge cbc-grade--{{ $grade['tier'] }}"
        title="{{ $grade['label'] }} ({{ number_format($grade['percent'], 1) }}%)">
    {{ $grade['short'] }}
  </span>
@endif
