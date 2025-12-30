@if(session('success') || session('warning') || session('error') || session('status') || $errors->any())
    <div class="mb-3">
        @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger', 'status' => 'info'] as $key => $style)
            @if(session($key))
                <div class="alert alert-{{ $style }} alert-dismissible fade show" role="alert">
                    {{ session($key) }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
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

