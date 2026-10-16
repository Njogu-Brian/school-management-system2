@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('website.partials.header', [
            'title' => 'Replace image before deleting',
            'icon' => 'bi bi-arrow-left-right',
            'subtitle' => $item->title,
        ])

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="settings-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <p class="small text-muted mb-2">Current image</p>
                        <img src="{{ $item->url() }}" alt="" class="img-fluid rounded border" style="width:100%;max-height:280px;object-fit:contain;background:#f4f4f5">
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-2">Replacing with</p>
                        <div id="replacement-preview-wrap" class="rounded border d-flex align-items-center justify-content-center text-muted" style="min-height:180px;background:#f4f4f5">
                            <img id="replacement-preview" alt="" class="img-fluid rounded d-none" style="width:100%;max-height:280px;object-fit:contain">
                            <span id="replacement-empty">Select an image below</span>
                        </div>
                        <p id="replacement-caption" class="small mt-2 mb-0"></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-2">This image is still used. Pick another image from the library, or upload a new one. Every place that uses this file will switch to the replacement, then the original is deleted.</p>
                        <ul class="mb-0">
                            @foreach($usages as $usage)
                                <li>{{ $usage['type'] }}: {{ $usage['label'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('website.media.replace.store', $item) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <input type="hidden" name="replacement_id" id="replacement_id" value="">
                    <div class="col-12">
                        <label class="form-label">Use an existing image</label>
                        <div class="row g-2" style="max-height:460px;overflow:auto">
                            @foreach($library as $candidate)
                                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                    <button type="button"
                                        class="btn p-1 border w-100 text-start replacement-choice"
                                        data-id="{{ $candidate->id }}"
                                        data-url="{{ $candidate->url() }}"
                                        data-title="{{ $candidate->title }}">
                                        <img src="{{ $candidate->optimizedUrl() ?? $candidate->url() }}" alt="" class="rounded" style="aspect-ratio:1;object-fit:cover;width:100%">
                                        <span class="d-block small text-truncate mt-1">{{ $candidate->title }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Or upload a new image</label>
                        <input type="file" name="replacement_file" id="replacement_file" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Title for a new upload</label>
                        <input type="text" name="replacement_title" class="form-control" value="Replacement for {{ $item->title }}">
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-settings-primary">Replace and delete</button>
                        <a href="{{ route('website.media.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <script>
            (function () {
                var preview = document.getElementById('replacement-preview');
                var empty = document.getElementById('replacement-empty');
                var caption = document.getElementById('replacement-caption');
                var hidden = document.getElementById('replacement_id');
                var fileInput = document.getElementById('replacement_file');

                function show(url, title) {
                    preview.src = url;
                    preview.classList.remove('d-none');
                    empty.classList.add('d-none');
                    caption.textContent = title || '';
                }

                document.querySelectorAll('.replacement-choice').forEach(function (button) {
                    button.addEventListener('click', function () {
                        document.querySelectorAll('.replacement-choice').forEach(function (other) {
                            other.classList.remove('border-primary', 'border-2');
                        });
                        button.classList.add('border-primary', 'border-2');
                        hidden.value = button.getAttribute('data-id');
                        if (fileInput) fileInput.value = '';
                        show(button.getAttribute('data-url'), button.getAttribute('data-title'));
                    });
                });

                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        var file = fileInput.files && fileInput.files[0];
                        if (!file) return;
                        hidden.value = '';
                        document.querySelectorAll('.replacement-choice').forEach(function (other) {
                            other.classList.remove('border-primary', 'border-2');
                        });
                        show(URL.createObjectURL(file), file.name);
                    });
                }
            })();
        </script>
    </div>
</div>
@endsection
