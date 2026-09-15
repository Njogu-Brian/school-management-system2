@php
  $selectedCode = $selectedCode ?? '+254';
  $codeOptions = $countryCodes ?? \App\Support\CountryDialCodes::options();
@endphp
@foreach($codeOptions as $key => $cc)
  @php
    if (is_array($cc)) {
      $optCode = (string) ($cc['code'] ?? '');
      $optLabel = (string) ($cc['label'] ?? $optCode);
    } else {
      $optCode = (string) $key;
      $optLabel = (string) $cc;
    }
  @endphp
  @if($optCode !== '')
    <option value="{{ $optCode }}" @selected($selectedCode == $optCode)>{{ $optLabel }}</option>
  @endif
@endforeach
