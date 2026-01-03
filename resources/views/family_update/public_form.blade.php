<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $schoolNameSetting = \App\Models\Setting::where('key', 'school_name')->first();
        $schoolLogoSetting = \App\Models\Setting::where('key', 'school_logo')->first();
        $faviconSetting = \App\Models\Setting::where('key', 'favicon')->first();
        $appName = $schoolNameSetting?->value ?? config('app.name', 'School Management System');
        $logoSetting = $schoolLogoSetting?->value;
        $faviconSettingValue = $faviconSetting?->value ?? $logoSetting;
        $logoUrl = null;
        if ($logoSetting && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoSetting)) {
            $logoUrl = \Illuminate\Support\Facades\Storage::url($logoSetting);
        } elseif ($logoSetting && file_exists(public_path('images/'.$logoSetting))) {
            $logoUrl = asset('images/'.$logoSetting);
        } else {
            $logoUrl = asset('images/logo.png');
        }
        $faviconUrl = null;
        if ($faviconSettingValue && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconSettingValue)) {
            $faviconUrl = \Illuminate\Support\Facades\Storage::url($faviconSettingValue);
        } elseif ($faviconSettingValue && file_exists(public_path('images/'.$faviconSettingValue))) {
            $faviconUrl = asset('images/'.$faviconSettingValue);
        } elseif ($logoSetting && file_exists(public_path('images/'.$logoSetting))) {
            $faviconUrl = asset('images/'.$logoSetting);
        } else {
            $faviconUrl = asset('images/logo.png');
        }
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details Update</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root {
            --brand-primary: {{ setting('finance_primary_color', '#0f766e') }};
            --brand-accent: {{ setting('finance_secondary_color', '#14b8a6') }};
            --brand-bg: #f5f7fb;
            --brand-surface: #ffffff;
            --brand-border: #e5e7eb;
            --brand-text: #0f172a;
            --brand-muted: #6b7280;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--brand-bg);
            color: var(--brand-text);
        }
        .hero {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
            color: #fff;
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .form-shell {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
        }
        .form-section {
            border: 1px solid var(--brand-border);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
        }
        .section-header {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--brand-muted);
        }
        .badge-pill {
            border-radius: 999px;
            padding: 6px 12px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            font-weight: 600;
        }
        .form-label {
            font-weight: 600;
            color: var(--brand-text);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border-color: var(--brand-border);
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem rgba(15,118,110,0.15);
        }
        .upload-hint {
            font-size: 0.85rem;
            color: var(--brand-muted);
        }
        @media (max-width: 576px) {
            .hero {
                padding: 20px 18px;
            }
            .form-shell {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-10">
            <div class="hero mb-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $logoUrl }}" alt="Logo" style="width:56px;height:56px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,0.15);padding:8px;">
                    <div>
                        <div class="fw-semibold text-uppercase small" style="letter-spacing:0.6px;">Secure Update</div>
                        <h4 class="mb-0">Student Details</h4>
                        <small class="opacity-75">Family ID: {{ $family->id }}</small>
                    </div>
                </div>
                <span class="badge-pill">Encrypted Link</span>
            </div>

            <div class="form-shell p-4">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <form action="{{ route('family-update.submit', $link->token) }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @foreach($students as $stu)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="section-header text-uppercase mb-0">Student Details</h6>
                            <span class="badge bg-light text-dark">Admission #{{ $stu->admission_number }}</span>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">{{ $stu->first_name }} {{ $stu->last_name }}</div>
                            </div>
                            <input type="hidden" name="students[{{ $stu->id }}][id]" value="{{ $stu->id }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="students[{{ $stu->id }}][first_name]" class="form-control" value="{{ old('students.'.$stu->id.'.first_name', $stu->first_name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="students[{{ $stu->id }}][middle_name]" class="form-control" value="{{ old('students.'.$stu->id.'.middle_name', $stu->middle_name) }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="students[{{ $stu->id }}][last_name]" class="form-control" value="{{ old('students.'.$stu->id.'.last_name', $stu->last_name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gender</label>
                                    <select name="students[{{ $stu->id }}][gender]" class="form-select" required>
                                        @php 
                                            $currentGender = old('students.'.$stu->id.'.gender', strtolower($stu->gender ?? ''));
                                        @endphp
                                        <option value="male" @selected($currentGender=='male')>Male</option>
                                        <option value="female" @selected($currentGender=='female')>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="students[{{ $stu->id }}][dob]" class="form-control" value="{{ old('students.'.$stu->id.'.dob', $stu->dob ? \Carbon\Carbon::parse($stu->dob)->format('Y-m-d') : '') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Classroom (view only)</label>
                                    <input type="text" class="form-control" value="{{ $stu->classroom->name ?? '—' }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="students[{{ $stu->id }}][has_allergies]" value="1" id="has_allergies_{{ $stu->id }}" @checked(old('students.'.$stu->id.'.has_allergies', $stu->has_allergies))>
                                        <label class="form-check-label" for="has_allergies_{{ $stu->id }}">Has allergies?</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="students[{{ $stu->id }}][is_fully_immunized]" value="1" id="fully_immunized_{{ $stu->id }}" @checked(old('students.'.$stu->id.'.is_fully_immunized', $stu->is_fully_immunized))>
                                        <label class="form-check-label" for="fully_immunized_{{ $stu->id }}">Fully immunized</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Allergies Notes</label>
                                    <textarea name="students[{{ $stu->id }}][allergies_notes]" class="form-control" rows="2">{{ old('students.'.$stu->id.'.allergies_notes', $stu->allergies_notes) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Passport Photo</label>
                                    <input type="file" name="students[{{ $stu->id }}][passport_photo]" class="form-control" accept="image/*" capture="environment">
                                    @if($stu->photo_path)
                                        <small class="upload-hint d-block mt-1"><a target="_blank" href="{{ Storage::url($stu->photo_path) }}">Current photo</a></small>
                                    @else
                                        <small class="upload-hint d-block mt-1">Accepted: JPG/PNG up to 4 MB.</small>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Birth Certificate / Notification</label>
                                    <input type="file" name="students[{{ $stu->id }}][birth_certificate]" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                    @if($stu->birth_certificate_path)
                                        <small class="upload-hint d-block mt-1"><a target="_blank" href="{{ Storage::url($stu->birth_certificate_path) }}">Current file</a></small>
                                    @else
                                        <small class="upload-hint d-block mt-1">Accepted: PDF/JPG/PNG up to 5 MB.</small>
                                    @endif
                                        @if($stu->birth_certificate_path)
                                            <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($stu->birth_certificate_path) }}">Current file</a></small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <h6 class="text-uppercase text-muted mb-3">Parent / Guardian</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    <option value="married" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='married')>Married</option>
                                    <option value="single_parent" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='single_parent')>Single Parent</option>
                                    <option value="co_parenting" @selected(old('marital_status', $family->students->first()->parent->marital_status ?? '')=='co_parenting')>Co-parenting</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father Name</label>
                                <input type="text" name="father_name" class="form-control" value="{{ old('father_name', $family->students->first()->parent->father_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father ID Number</label>
                                <input type="text" name="father_id_number" class="form-control" value="{{ old('father_id_number', $family->students->first()->parent->father_id_number ?? '') }}">
                            </div>
                                <div class="col-md-6">
                                    <label class="form-label">Father Phone</label>
                                    @php
                                        $fatherPhone = old('father_phone', $family->students->first()->parent->father_phone ?? '');
                                        $fatherCountryCode = old('father_phone_country_code', $family->students->first()->parent->father_phone_country_code ?? '+254');
                                        // Normalize +KE to +254
                                        $fatherCountryCode = strtolower($fatherCountryCode) === '+ke' || strtolower($fatherCountryCode) === 'ke' ? '+254' : $fatherCountryCode;
                                        $fatherLocalPhone = extract_local_phone($fatherPhone, $fatherCountryCode);
                                    @endphp
                                    <div class="input-group phone-input-group">
                                        <span class="input-group-text phone-flag" id="father_phone_prefix">+254</span>
                                        <select name="father_phone_country_code" class="form-select flex-grow-0 phone-code-select" data-target="father_phone" style="max-width:170px">
                                            @foreach($countryCodes as $code => $label)
                                                <option value="{{ $code }}" @selected($fatherCountryCode==$code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="father_phone" id="father_phone" class="form-control phone-input" value="{{ $fatherLocalPhone }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="father_phone_help">
                                    </div>
                                    <small class="upload-hint d-block" id="father_phone_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                                </div>
                            <div class="col-md-6">
                                <label class="form-label">Father WhatsApp</label>
                                    @php
                                        $fatherWhatsapp = old('father_whatsapp', $family->students->first()->parent->father_whatsapp ?? '');
                                        $fatherWhatsappLocal = extract_local_phone($fatherWhatsapp, $fatherCountryCode);
                                    @endphp
                                    <input type="text" name="father_whatsapp" id="father_whatsapp" class="form-control phone-input" value="{{ $fatherWhatsappLocal }}" placeholder="Same code as phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father Email</label>
                                <input type="email" name="father_email" class="form-control" value="{{ old('father_email', $family->students->first()->parent->father_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father ID Document</label>
                                <input type="file" name="father_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                @if(optional($family->students->first()->parent)->father_id_document)
                                    <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($family->students->first()->parent->father_id_document) }}">Current file</a></small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Mother Name</label>
                                <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name', $family->students->first()->parent->mother_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother ID Number</label>
                                <input type="text" name="mother_id_number" class="form-control" value="{{ old('mother_id_number', $family->students->first()->parent->mother_id_number ?? '') }}">
                            </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mother Phone</label>
                                    @php
                                        $motherPhone = old('mother_phone', $family->students->first()->parent->mother_phone ?? '');
                                        $motherCountryCode = old('mother_phone_country_code', $family->students->first()->parent->mother_phone_country_code ?? '+254');
                                        // Normalize +KE to +254
                                        $motherCountryCode = strtolower($motherCountryCode) === '+ke' || strtolower($motherCountryCode) === 'ke' ? '+254' : $motherCountryCode;
                                        $motherLocalPhone = extract_local_phone($motherPhone, $motherCountryCode);
                                    @endphp
                                    <div class="input-group phone-input-group">
                                        <span class="input-group-text phone-flag" id="mother_phone_prefix">+254</span>
                                        <select name="mother_phone_country_code" class="form-select flex-grow-0 phone-code-select" data-target="mother_phone" style="max-width:170px">
                                            @foreach($countryCodes as $code => $label)
                                                <option value="{{ $code }}" @selected($motherCountryCode==$code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="mother_phone" id="mother_phone" class="form-control phone-input" value="{{ $motherLocalPhone }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="mother_phone_help">
                                    </div>
                                    <small class="upload-hint d-block" id="mother_phone_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                                </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother WhatsApp</label>
                                    @php
                                        $motherWhatsapp = old('mother_whatsapp', $family->students->first()->parent->mother_whatsapp ?? '');
                                        $motherWhatsappLocal = extract_local_phone($motherWhatsapp, $motherCountryCode);
                                    @endphp
                                    <input type="text" name="mother_whatsapp" id="mother_whatsapp" class="form-control phone-input" value="{{ $motherWhatsappLocal }}" placeholder="Same code as phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother Email</label>
                                <input type="email" name="mother_email" class="form-control" value="{{ old('mother_email', $family->students->first()->parent->mother_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother ID Document</label>
                                <input type="file" name="mother_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                                @if(optional($family->students->first()->parent)->mother_id_document)
                                    <small class="text-muted d-block mt-1"><a target="_blank" href="{{ Storage::url($family->students->first()->parent->mother_id_document) }}">Current file</a></small>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name', $family->students->first()->parent->guardian_name ?? '') }}">
                            </div>
                                <div class="col-md-6">
                                    <label class="form-label">Guardian Phone</label>
                                    @php
                                        $guardianPhone = old('guardian_phone', $family->students->first()->parent->guardian_phone ?? '');
                                        $guardianCountryCode = old('guardian_phone_country_code', $family->students->first()->parent->guardian_phone_country_code ?? '+254');
                                        // Normalize +KE to +254
                                        $guardianCountryCode = strtolower($guardianCountryCode) === '+ke' || strtolower($guardianCountryCode) === 'ke' ? '+254' : $guardianCountryCode;
                                        $guardianLocalPhone = extract_local_phone($guardianPhone, $guardianCountryCode);
                                    @endphp
                                    <div class="input-group phone-input-group">
                                        <span class="input-group-text phone-flag" id="guardian_phone_prefix">+254</span>
                                        <select name="guardian_phone_country_code" class="form-select flex-grow-0 phone-code-select" data-target="guardian_phone" style="max-width:170px">
                                            @foreach($countryCodes as $code => $label)
                                                <option value="{{ $code }}" @selected($guardianCountryCode==$code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="guardian_phone" id="guardian_phone" class="form-control phone-input" value="{{ $guardianLocalPhone }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="guardian_phone_help">
                                    </div>
                                    <small class="upload-hint d-block" id="guardian_phone_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                                </div>
                            <div class="col-md-6">
                                <label class="form-label">Guardian Relationship</label>
                                <input type="text" name="guardian_relationship" class="form-control" value="{{ old('guardian_relationship', $family->students->first()->parent->guardian_relationship ?? '') }}">
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Emergency & Medical</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name</label>
                                <small class="text-muted d-block mb-1">Person we call if parents/guardians cannot be reached.</small>
                                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $family->students->first()->emergency_contact_name ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Phone</label>
                                @php
                                    $emergencyPhone = old('emergency_contact_phone', $family->students->first()->emergency_contact_phone ?? '');
                                    $emergencyCountryCode = old('emergency_phone_country_code', '+254');
                                    // Normalize +KE to +254
                                    $emergencyCountryCode = strtolower($emergencyCountryCode) === '+ke' || strtolower($emergencyCountryCode) === 'ke' ? '+254' : $emergencyCountryCode;
                                    $emergencyLocalPhone = extract_local_phone($emergencyPhone, $emergencyCountryCode);
                                @endphp
                                <div class="input-group phone-input-group">
                                    <span class="input-group-text phone-flag" id="emergency_phone_prefix">+254</span>
                                    <select name="emergency_phone_country_code" class="form-select flex-grow-0 phone-code-select" data-target="emergency_contact_phone" style="max-width:170px">
                                        @foreach($countryCodes as $code => $label)
                                            <option value="{{ $code }}" @selected($emergencyCountryCode==$code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control phone-input" value="{{ $emergencyLocalPhone }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="emergency_phone_help">
                                </div>
                                <small class="upload-hint d-block" id="emergency_phone_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preferred Hospital / Medical Facility</label>
                                <input type="text" name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital', $family->students->first()->preferred_hospital ?? '') }}">
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Residential</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Residential Area</label>
                                <input type="text" name="residential_area" class="form-control" value="{{ old('residential_area', $family->students->first()->residential_area ?? '') }}">
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Save Updates</button>
                            <div class="text-muted small text-center">You can revisit this link anytime to make further updates.</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const phoneRules = {
            '+254': {placeholder: '7XXXXXXXX', pattern: '(7|1)[0-9]{8}'},
            '+1':   {placeholder: '2XXXXXXXXX', pattern: '[0-9]{9,12}'},
            '+971': {placeholder: '5XXXXXXXX', pattern: '[0-9]{7,10}'},
            '+974': {placeholder: '3XXXXXXX', pattern: '[0-9]{7,10}'},
            '+86':  {placeholder: '1XXXXXXXXXX', pattern: '[0-9]{8,12}'},
            '+81':  {placeholder: '8XXXXXXXX', pattern: '[0-9]{8,12}'},
            '+61':  {placeholder: '4XXXXXXXX', pattern: '[0-9]{8,11}'},
            '+49':  {placeholder: '15XXXXXXX', pattern: '[0-9]{7,12}'},
            '+358': {placeholder: '4XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+44':  {placeholder: '7XXXXXXXXX', pattern: '[0-9]{8,12}'},
            '+27':  {placeholder: '6XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+256': {placeholder: '7XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+255': {placeholder: '7XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+250': {placeholder: '7XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+257': {placeholder: '7XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+211': {placeholder: '9XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+260': {placeholder: '9XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+263': {placeholder: '7XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+265': {placeholder: '9XXXXXXXX', pattern: '[0-9]{7,11}'},
            '+234': {placeholder: '8XXXXXXXX', pattern: '[0-9]{8,12}'},
        };

        document.querySelectorAll('.phone-code-select').forEach(function (select) {
            const targetId = select.dataset.target;
            const input = document.getElementById(targetId);
            const prefix = document.getElementById(`${targetId}_prefix`);
            const hint = document.getElementById(`${targetId}_help`);

            const applyRule = () => {
                const code = select.value;
                const rule = phoneRules[code] || {placeholder: 'number', pattern: '[0-9]{6,12}'};
                if (prefix) prefix.textContent = code;
                if (input) {
                    input.placeholder = rule.placeholder;
                    input.pattern = rule.pattern;
                }
                if (hint) {
                    hint.textContent = code === '+254'
                        ? 'Kenyan format: starts with 7 or 1 then 8 digits.'
                        : 'Enter 6-12 digits for selected country.';
                }
            };

            select.addEventListener('change', applyRule);
            applyRule();
        });
    });
</script>
</body>
</html>
