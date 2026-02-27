@if(session('success') || session('warning') || session('error') || session('status') || $errors->any() || session('skipped_recipients'))
    <div class="mb-3">
        @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger', 'status' => 'info'] as $key => $style)
            @if(session($key))
                <div class="alert alert-{{ $style }} alert-dismissible fade show" role="alert">
                    {{ session($key) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @if(session('skipped_recipients') && in_array($key, ['warning', 'success']))
                    <div class="alert alert-secondary alert-dismissible fade show" role="alert">
                        <strong>Skipped recipients (invalid/non-Kenyan phone):</strong>
                        <details class="mt-2">
                            <summary class="btn btn-sm btn-outline-secondary">Show list ({{ count(session('skipped_recipients')) }})</summary>
                            <ul class="mt-2 mb-0 small">
                                @foreach(session('skipped_recipients') as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </details>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-1">Please fix the following:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>
@endif

