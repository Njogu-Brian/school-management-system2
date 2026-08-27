@php
  $kind = $kind ?? 'document';
  $hint = $hint ?? '';
@endphp
<div class="file-upload-wrapper" data-media-picker="{{ $kind }}">
  {{-- Canonical input is what the form submits. Source inputs open the right phone picker. --}}
  <input type="file" name="{{ $name }}" id="{{ $id }}" class="file-input-hidden js-media-canonical" tabindex="-1">
  @if($kind === 'document')
    <input type="file" id="{{ $id }}_files" class="file-input-hidden js-media-source" data-media-source="files" accept="application/pdf,.pdf" tabindex="-1">
  @endif
  <input type="file" id="{{ $id }}_gallery" class="file-input-hidden js-media-source" data-media-source="gallery" accept="image/*" tabindex="-1">
  <input type="file" id="{{ $id }}_camera" class="file-input-hidden js-media-source" data-media-source="camera" accept="image/*" capture="environment" tabindex="-1">
  <div class="file-upload-buttons">
    @if($kind === 'document')
      <label for="{{ $id }}_files" class="file-upload-btn">
        <i class="bi bi-folder2-open"></i>
        <span>Browse files</span>
      </label>
    @endif
    <label for="{{ $id }}_gallery" class="file-upload-btn">
      <i class="bi bi-image"></i>
      <span>Choose photo</span>
    </label>
    <label for="{{ $id }}_camera" class="file-upload-btn">
      <i class="bi bi-camera"></i>
      <span>Take photo</span>
    </label>
  </div>
  <div id="{{ $id }}_preview" class="file-preview js-media-preview" style="display:none;"></div>
  @if($hint !== '')
    <small class="upload-hint d-block mt-1">{{ $hint }}</small>
  @endif
</div>
