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

        <hr class="my-4">

        {{-- Finance Module Color Settings --}}
        <h5 class="mb-3">🎨 Finance Module Colors & Typography</h5>
        <p class="text-muted">Customize the appearance of all finance pages</p>

        <div class="row g-3">
            {{-- Primary Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Primary Color</label>
                <input type="color" class="form-control form-control-color" name="finance_primary_color" 
                       value="{{ $settings['finance_primary_color']->value ?? '#6366f1' }}">
                <small class="text-muted">Main brand color for headers and primary actions</small>
            </div>

            {{-- Secondary Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Secondary Color</label>
                <input type="color" class="form-control form-control-color" name="finance_secondary_color" 
                       value="{{ $settings['finance_secondary_color']->value ?? '#764ba2' }}">
                <small class="text-muted">Secondary accent color for gradients</small>
            </div>

            {{-- Success Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Success Color</label>
                <input type="color" class="form-control form-control-color" name="finance_success_color" 
                       value="{{ $settings['finance_success_color']->value ?? '#10b981' }}">
                <small class="text-muted">Color for success states and positive amounts</small>
            </div>

            {{-- Warning Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Warning Color</label>
                <input type="color" class="form-control form-control-color" name="finance_warning_color" 
                       value="{{ $settings['finance_warning_color']->value ?? '#f59e0b' }}">
                <small class="text-muted">Color for warnings and partial payments</small>
            </div>

            {{-- Danger Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Danger Color</label>
                <input type="color" class="form-control form-control-color" name="finance_danger_color" 
                       value="{{ $settings['finance_danger_color']->value ?? '#ef4444' }}">
                <small class="text-muted">Color for errors and overdue items</small>
            </div>

            {{-- Info Color --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Info Color</label>
                <input type="color" class="form-control form-control-color" name="finance_info_color" 
                       value="{{ $settings['finance_info_color']->value ?? '#06b6d4' }}">
                <small class="text-muted">Color for informational elements</small>
            </div>

            {{-- Primary Font --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Primary Font Family</label>
                <select class="form-select" name="finance_primary_font">
                    <option value="Inter" {{ ($settings['finance_primary_font']->value ?? 'Inter') == 'Inter' ? 'selected' : '' }}>Inter</option>
                    <option value="Poppins" {{ ($settings['finance_primary_font']->value ?? '') == 'Poppins' ? 'selected' : '' }}>Poppins</option>
                    <option value="Roboto" {{ ($settings['finance_primary_font']->value ?? '') == 'Roboto' ? 'selected' : '' }}>Roboto</option>
                    <option value="Open Sans" {{ ($settings['finance_primary_font']->value ?? '') == 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                    <option value="Lato" {{ ($settings['finance_primary_font']->value ?? '') == 'Lato' ? 'selected' : '' }}>Lato</option>
                    <option value="Montserrat" {{ ($settings['finance_primary_font']->value ?? '') == 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                </select>
                <small class="text-muted">Main font for finance pages</small>
            </div>

            {{-- Heading Font --}}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Heading Font Family</label>
                <select class="form-select" name="finance_heading_font">
                    <option value="Poppins" {{ ($settings['finance_heading_font']->value ?? 'Poppins') == 'Poppins' ? 'selected' : '' }}>Poppins</option>
                    <option value="Inter" {{ ($settings['finance_heading_font']->value ?? '') == 'Inter' ? 'selected' : '' }}>Inter</option>
                    <option value="Roboto" {{ ($settings['finance_heading_font']->value ?? '') == 'Roboto' ? 'selected' : '' }}>Roboto</option>
                    <option value="Open Sans" {{ ($settings['finance_heading_font']->value ?? '') == 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                    <option value="Lato" {{ ($settings['finance_heading_font']->value ?? '') == 'Lato' ? 'selected' : '' }}>Lato</option>
                    <option value="Montserrat" {{ ($settings['finance_heading_font']->value ?? '') == 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                </select>
                <small class="text-muted">Font for headings and titles</small>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Save Branding Settings</button>
            <a href="#" class="btn btn-outline-secondary ms-2" onclick="resetFinanceColors()">Reset to Defaults</a>
        </div>
    </form>
</div>

<script>
function resetFinanceColors() {
    if (confirm('Reset all finance colors and fonts to default values?')) {
        document.querySelector('input[name="finance_primary_color"]').value = '#6366f1';
        document.querySelector('input[name="finance_secondary_color"]').value = '#764ba2';
        document.querySelector('input[name="finance_success_color"]').value = '#10b981';
        document.querySelector('input[name="finance_warning_color"]').value = '#f59e0b';
        document.querySelector('input[name="finance_danger_color"]').value = '#ef4444';
        document.querySelector('input[name="finance_info_color"]').value = '#06b6d4';
        document.querySelector('select[name="finance_primary_font"]').value = 'Inter';
        document.querySelector('select[name="finance_heading_font"]').value = 'Poppins';
    }
}
</script>
