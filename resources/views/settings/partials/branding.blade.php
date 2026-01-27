@php
    $financePrimary = $settings['finance_primary_color']->value ?? '#6366f1';
    $financeSecondary = $settings['finance_secondary_color']->value ?? '#764ba2';
    $financeSuccess = $settings['finance_success_color']->value ?? '#10b981';
    $financeWarning = $settings['finance_warning_color']->value ?? '#f59e0b';
    $financeDanger = $settings['finance_danger_color']->value ?? '#ef4444';
    $financeInfo = $settings['finance_info_color']->value ?? '#06b6d4';
@endphp

<div class="tab-pane fade" id="tab-branding" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Branding & Theme</h5>
                <div class="section-note">Refresh the visual identity used on login, invoices, and finance pages.</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="toggle-pill mb-0" title="Enable brand colors across the app">
                    <input type="checkbox" id="brandThemeToggle">
                    <span class="track">
                        <i class="bi bi-palette icon"></i>
                        <i class="bi bi-slash-circle icon"></i>
                        <span class="thumb"></span>
                    </span>
                    <span class="label">Brand Colors</span>
                </label>
                <span class="pill-badge"><i class="bi bi-palette"></i> Visual system</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update.branding') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="subtle-hero h-100">
                            <div class="section-title">School Logo</div>
                            <div class="section-note">Shown on the login page, receipts, and PDFs.</div>
                            <input type="file" class="form-control mt-2" name="school_logo" accept="image/*">
                            @if(isset($settings['school_logo']) && $settings['school_logo']->value)
                                <div class="mt-3">
                                    <img src="{{ public_image_url($settings['school_logo']->value) }}"
                                         alt="Logo" class="border rounded shadow-sm"
                                         style="max-height: 80px; max-width: 100%;">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="subtle-hero h-100">
                            <div class="section-title">Login Background</div>
                            <div class="section-note">Use a 16:9 image for best clarity.</div>
                            <input type="file" class="form-control mt-2" name="login_background" accept="image/*">
                            @if(isset($settings['login_background']) && $settings['login_background']->value)
                                <div class="mt-3">
                                    <img src="{{ public_image_url($settings['login_background']->value) }}"
                                         alt="Background" class="border rounded shadow-sm"
                                         style="max-height: 140px; max-width: 100%; object-fit: cover;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="settings-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-brush"></i> Finance Theme
                        </h5>
                        <div class="section-note mt-1 mb-0">Live across finance and votehead pages.</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Primary Color</label>
                                <input type="color" class="form-control form-control-color" name="finance_primary_color"
                                       value="{{ $financePrimary }}">
                                <div class="form-note mt-1">Headers, primary buttons</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Secondary Color</label>
                                <input type="color" class="form-control form-control-color" name="finance_secondary_color"
                                       value="{{ $financeSecondary }}">
                                <div class="form-note mt-1">Gradients & accents</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Surface</label>
                                <input type="color" class="form-control form-control-color" name="finance_surface_color"
                                       value="{{ $settings['finance_surface_color']->value ?? '#ffffff' }}">
                                <div class="form-note mt-1">Cards and panels background.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Border</label>
                                <input type="color" class="form-control form-control-color" name="finance_border_color"
                                       value="{{ $settings['finance_border_color']->value ?? '#e5e7eb' }}">
                                <div class="form-note mt-1">Table and card outlines.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Text</label>
                                <input type="color" class="form-control form-control-color" name="finance_text_color"
                                       value="{{ $settings['finance_text_color']->value ?? '#0f172a' }}">
                                <div class="form-note mt-1">Primary text color.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Muted Text</label>
                                <input type="color" class="form-control form-control-color" name="finance_muted_color"
                                       value="{{ $settings['finance_muted_color']->value ?? '#6b7280' }}">
                                <div class="form-note mt-1">Helper and secondary text.</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Success</label>
                                <input type="color" class="form-control form-control-color" name="finance_success_color"
                                       value="{{ $financeSuccess }}">
                                <div class="form-note mt-1">Positive states</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Warning</label>
                                <input type="color" class="form-control form-control-color" name="finance_warning_color"
                                       value="{{ $financeWarning }}">
                                <div class="form-note mt-1">Alerts and partials</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Danger</label>
                                <input type="color" class="form-control form-control-color" name="finance_danger_color"
                                       value="{{ $financeDanger }}">
                                <div class="form-note mt-1">Errors & overdue</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Info</label>
                                <input type="color" class="form-control form-control-color" name="finance_info_color"
                                       value="{{ $financeInfo }}">
                                <div class="form-note mt-1">Hints & chips</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Primary Font Family</label>
                                <select class="form-select" name="finance_primary_font">
                                    @foreach(['Inter','Poppins','Roboto','Open Sans','Lato','Montserrat','Nunito','Manrope'] as $font)
                                        <option value="{{ $font }}" style="font-family:'{{ $font }}', sans-serif"
                                            {{ ($settings['finance_primary_font']->value ?? 'Inter') == $font ? 'selected' : '' }}>
                                            {{ $font }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-note mt-1">Body text across finance screens.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Heading Font Family</label>
                                <select class="form-select" name="finance_heading_font">
                                    @foreach(['Poppins','Inter','Roboto','Open Sans','Lato','Montserrat','Nunito','Manrope'] as $font)
                                        <option value="{{ $font }}" style="font-family:'{{ $font }}', sans-serif"
                                            {{ ($settings['finance_heading_font']->value ?? 'Poppins') == $font ? 'selected' : '' }}>
                                            {{ $font }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-note mt-1">For headings and KPIs.</div>
                            </div>
                        </div>

                        <div class="mt-3 gradient-preview" style="background: linear-gradient(135deg, {{ $financePrimary }} 0%, {{ $financeSecondary }} 100%);">
                            Finance gradient preview
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-settings-primary px-4">Save Branding</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetFinanceColors()">Reset to Defaults</button>
                </div>
            </form>
        </div>
    </div>
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
