<div class="tab-pane fade" id="branding" role="tabpanel">
    <form method="POST" action="{{ route('settings.update.branding') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label>School Logo</label>
            <input type="file" class="form-control" name="school_logo">
            @if(isset($settings['school_logo']) && $settings['school_logo']->value)
                <img src="{{ asset('storage/' . $settings['school_logo']->value) }}" alt="Logo" class="mt-2" height="60">
            @endif
        </div>
        <div class="mb-3">
            <label>Login Background</label>
            <input type="file" class="form-control" name="login_background">
            @if(isset($settings['login_background']) && $settings['login_background']->value)
                <img src="{{ asset('storage/' . $settings['login_background']->value) }}" alt="Background" class="mt-2" height="60">
            @endif
        </div>
        <button class="btn btn-primary">Save Branding</button>
    </form>
</div>
