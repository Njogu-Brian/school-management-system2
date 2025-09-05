<div class="tab-pane fade" id="branding" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.branding') }}" enctype="multipart/form-data">
        @csrf

        {{-- School Logo --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">School Logo</label>
            <input type="file" class="form-control" name="school_logo" accept="image/*">
            
            @if(isset($settings['school_logo']) && $settings['school_logo']->value)
                <div class="mt-2">
                    <img src="{{ asset('images/' . $settings['school_logo']->value) }}" 
                         alt="Logo" class="border rounded shadow-sm" 
                         style="max-height: 70px; max-width: 100%;">
                </div>
            @endif
        </div>

        {{-- Login Background --}}
        <div class="mb-3">
            <label class="form-label fw-semibold">Login Background</label>
            <input type="file" class="form-control" name="login_background" accept="image/*">
            
            @if(isset($settings['login_background']) && $settings['login_background']->value)
                <div class="mt-2">
                    <img src="{{ asset('images/' . $settings['login_background']->value) }}" 
                         alt="Background" class="border rounded shadow-sm" 
                         style="max-height: 120px; max-width: 100%;">
                </div>
            @endif
        </div>

        <button class="btn btn-primary">Save Branding</button>
    </form>
</div>
