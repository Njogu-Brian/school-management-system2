<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $settings = \App\Models\Setting::whereIn('key', ['school_name', 'school_logo', 'favicon'])->pluck('value', 'key');
        $schoolName = $settings['school_name'] ?? config('app.name', 'School Management System');
        $logoSetting = $settings['school_logo'] ?? null;
        $faviconSetting = $settings['favicon'] ?? $logoSetting;

        $resolveImage = function ($filename) {
            if (!$filename) return null;
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filename)) {
                return \Illuminate\Support\Facades\Storage::url($filename);
            }
            if (file_exists(public_path('images/'.$filename))) {
                return asset('images/'.$filename);
            }
            return null;
        };

        $logoUrl = $resolveImage($logoSetting) ?? asset('images/logo.png');
        $faviconUrl = $resolveImage($faviconSetting) ?? $logoUrl;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Admission Application - {{ $schoolName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    @php $hasVite = file_exists(public_path('build/manifest.json')); @endphp
    @if($hasVite)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @elseif(file_exists(public_path('css/app.css')))
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    <style>
        :root {
            --primary: #5b6bff;
            --gradient: linear-gradient(135deg, #5b6bff 0%, #8a5bff 100%);
        }
        body {
            background: var(--gradient);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        .form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 14px 50px rgba(0,0,0,0.12);
            padding: 2.5rem;
            max-width: 960px;
            margin: 0 auto;
        }
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eef1ff;
        }
        .form-header h1 {
            color: var(--primary);
            font-weight: 700;
        }
        .section-title {
            color: var(--primary);
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eef1ff;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .form-control, .form-select {
            border-radius: 10px;
        }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-primary:hover { filter: brightness(0.95); }
        .badge-soft {
            background: rgba(91,107,255,0.1);
            color: var(--primary);
            border: 1px solid rgba(91,107,255,0.3);
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <div class="mb-3">
                    <img src="{{ $logoUrl }}" alt="Logo" style="max-height: 72px;">
                </div>

                <h1 class="mb-2"><i class="bi bi-mortarboard"></i> Online Admission Application</h1>
                <p class="text-muted mb-1">{{ $schoolName }}</p>
                <p class="text-muted">Please fill in all required fields marked with <span class="text-danger">*</span></p>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle"></i> Please correct the following errors:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="admissionForm" action="{{ route('online-admissions.public-submit') }}" method="POST" enctype="multipart/form-data" novalidate>
                @csrf

                <h5 class="section-title"><i class="bi bi-person"></i> Student Information</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required-field">First Name</label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter the first name.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror" value="{{ old('middle_name') }}">
                        @error('middle_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Last Name</label>
                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please enter the last name.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Date of Birth</label>
                        <input type="date" name="dob" class="form-control @error('dob') is-invalid @enderror" value="{{ old('dob') }}" required>
                        @error('dob')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select the date of birth.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field">Gender</label>
                        <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select Gender</option>
                            <option value="Male" @selected(old('gender')=='Male')>Male</option>
                            <option value="Female" @selected(old('gender')=='Female')>Female</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback">Please select a gender.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Preferred Classroom</label>
                        <select name="preferred_classroom_id" id="preferred_classroom_id" class="form-select">
                            <option value="">Select Classroom</option>
                            @foreach($classrooms as $classroom)
                                <option data-creche="{{ isset($crecheId) && $crecheId == $classroom->id ? '1' : '0' }}" data-foundation="{{ isset($foundationId) && $foundationId == $classroom->id ? '1' : '0' }}" value="{{ $classroom->id }}" @selected(old('preferred_classroom_id')==$classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block" id="class-hint">
                            Creche: below 2½ years. Foundation: below 4 years. Final placement set by school.
                        </small>
                    </div>
                </div>

                <div class="row g-3 mt-2" id="previous-school-section" style="display:none;">
                    <div class="col-md-6">
                        <label class="form-label">Previous School Attended</label>
                        <input type="text" name="previous_school" class="form-control" value="{{ old('previous_school') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reason for Transfer</label>
                        <input type="text" name="transfer_reason" class="form-control" value="{{ old('transfer_reason') }}">
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-people"></i> Parent / Guardian</h5>
                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <option value="married" @selected(old('marital_status')=='married')>Married</option>
                            <option value="single_parent" @selected(old('marital_status')=='single_parent')>Single Parent</option>
                            <option value="co_parenting" @selected(old('marital_status')=='co_parenting')>Co-parenting</option>
                        </select>
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-person-badge"></i> Father Information</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Father's Name</label><input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}"></div>
                    <div class="col-md-6"><label class="form-label">Father's Phone</label>
                      <div class="input-group">
                        <select name="father_phone_country_code" class="form-select" style="max-width:140px">
                          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
                            <option value="{{ $cc['code'] }}" @selected(old('father_phone_country_code', '+254')==$cc['code'])>{{ $cc['label'] }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="father_phone" class="form-control" value="{{ old('father_phone') }}">
                      </div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Father's WhatsApp</label>
                      <div class="input-group">
                        <select name="father_phone_country_code_disabled" class="form-select" style="max-width:140px" disabled>
                          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
                            <option value="{{ $cc['code'] }}" @selected(old('father_phone_country_code', '+254')==$cc['code'])>{{ $cc['label'] }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="father_whatsapp" class="form-control" value="{{ old('father_whatsapp') }}" placeholder="WhatsApp number">
                      </div>
                      <small class="text-muted">Use same country code as phone.</small>
                    </div>
                    <div class="col-md-6"><label class="form-label">Father's Email</label><input type="email" name="father_email" class="form-control" value="{{ old('father_email') }}"></div>
                    <div class="col-md-6"><label class="form-label">Father's ID Number</label><input type="text" name="father_id_number" class="form-control" value="{{ old('father_id_number') }}"></div>
                    <div class="col-md-6"><label class="form-label">Father ID Document</label><input type="file" name="father_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                </div>

                <h5 class="section-title"><i class="bi bi-person-badge"></i> Mother Information</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Mother's Name</label><input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}"></div>
                    <div class="col-md-6"><label class="form-label">Mother's Phone</label>
                      <div class="input-group">
                        <select name="mother_phone_country_code" class="form-select" style="max-width:140px">
                          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
                            <option value="{{ $cc['code'] }}" @selected(old('mother_phone_country_code', '+254')==$cc['code'])>{{ $cc['label'] }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="mother_phone" class="form-control" value="{{ old('mother_phone') }}">
                      </div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Mother's WhatsApp</label>
                      <div class="input-group">
                        <select name="mother_phone_country_code_disabled" class="form-select" style="max-width:140px" disabled>
                          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
                            <option value="{{ $cc['code'] }}" @selected(old('mother_phone_country_code', '+254')==$cc['code'])>{{ $cc['label'] }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="mother_whatsapp" class="form-control" value="{{ old('mother_whatsapp') }}" placeholder="WhatsApp number">
                      </div>
                      <small class="text-muted">Use same country code as phone.</small>
                    </div>
                    <div class="col-md-6"><label class="form-label">Mother's Email</label><input type="email" name="mother_email" class="form-control" value="{{ old('mother_email') }}"></div>
                    <div class="col-md-6"><label class="form-label">Mother's ID Number</label><input type="text" name="mother_id_number" class="form-control" value="{{ old('mother_id_number') }}"></div>
                    <div class="col-md-6"><label class="form-label">Mother ID Document</label><input type="file" name="mother_id_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
                </div>

                <h5 class="section-title"><i class="bi bi-shield-check"></i> Guardian Information</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Guardian's Name</label><input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}"></div>
                    <div class="col-md-4"><label class="form-label">Guardian's Phone</label>
                      <div class="input-group">
                        <select name="guardian_phone_country_code" class="form-select" style="max-width:140px">
                          @foreach(($countryCodes ?? [['code'=>'+254','label'=>'Kenya (+254)']]) as $cc)
                            <option value="{{ $cc['code'] }}" @selected(old('guardian_phone_country_code', '+254')==$cc['code'])>{{ $cc['label'] }}</option>
                          @endforeach
                        </select>
                        <input type="text" name="guardian_phone" class="form-control" value="{{ old('guardian_phone') }}">
                      </div>
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-file-earmark"></i> Documents</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Passport Photo</label>
                        <input type="file" name="passport_photo" class="form-control" accept="image/*" capture="environment">
                        <small class="text-muted">Max 2MB (JPG, PNG)</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Birth Certificate</label>
                        <input type="file" name="birth_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png" capture="environment">
                        <small class="text-muted">Max 5MB (PDF, JPG, PNG)</small>
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-heart-pulse"></i> Medical & Emergency</h5>
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="has_allergies" name="has_allergies" value="1" @checked(old('has_allergies'))>
                      <label class="form-check-label" for="has_allergies">Has any allergies?</label>
                    </div>
                    <textarea name="allergies_notes" class="form-control mt-2" rows="2" placeholder="Specify allergies (if any)">{{ old('allergies_notes') }}</textarea>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="is_fully_immunized" name="is_fully_immunized" value="1" @checked(old('is_fully_immunized'))>
                      <label class="form-check-label" for="is_fully_immunized">Child is fully immunized</label>
                    </div>
                    <label class="form-label mt-3">Preferred Hospital / Medical Facility</label>
                    <input type="text" name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Emergency Contact Name</label>
                    <small class="text-muted d-block mb-1">Person we call if parents/guardians cannot be reached.</small>
                    <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Emergency Phone</label>
                    <input type="text" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}" placeholder="+2547XXXXXXXX">
                  </div>
                </div>

                <h5 class="section-title"><i class="bi bi-house-door"></i> Residential</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                    <label class="form-label required-field">Residential Area</label>
                    <input type="text" name="residential_area" class="form-control @error('residential_area') is-invalid @enderror" value="{{ old('residential_area') }}" placeholder="Estate / Area" required>
                    @error('residential_area')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback">Please enter the residential area.</div>
                  </div>
                </div>

                <h5 class="section-title"><i class="bi bi-bus-front"></i> Transport (Optional)</h5>
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="transport_needed" name="transport_needed" value="1" @checked(old('transport_needed', false))>
                            <label class="form-check-label" for="transport_needed">I need school transport</label>
                        </div>
                    </div>
                    <div class="col-md-6 transport-field">
                        <label class="form-label">Drop-off Point</label>
                        <select name="drop_off_point_id" id="drop_off_point_id" class="form-select">
                            <option value="">Select Drop-off Point</option>
                            @foreach($dropOffPoints as $point)
                                <option value="{{ $point->id }}" @selected(old('drop_off_point_id')==$point->id)>{{ $point->name }}</option>
                            @endforeach
                            <option value="other">Other (specify)</option>
                        </select>
                    </div>
                    <div class="col-md-6 transport-field">
                        <label class="form-label">If Other, type here</label>
                        <input type="text" name="drop_off_point_other" id="drop_off_point_other" class="form-control" value="{{ old('drop_off_point_other') }}" placeholder="Custom drop-off point">
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i> <strong>Note:</strong> After submission, your application will be reviewed. You will be contacted via phone or email regarding the status of your application.
                </div>

                <div id="validationAlert" class="alert alert-danger" style="display: none;">
                    <h6><i class="bi bi-exclamation-triangle"></i> Please correct the following:</h6>
                    <ul id="validationErrors" class="mb-0"></ul>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
                        <span class="submit-text"><i class="bi bi-send"></i> Submit Application</span>
                        <span class="submit-loading" style="display: none;">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Submitting...
                        </span>
                    </button>
                  <button type="submit" id="submitAddAnotherBtn" name="save_add_another" value="1" class="btn btn-outline-primary">
                    <span class="submit-text"><i class="bi bi-plus-circle"></i> Submit & Add Another</span>
                    <span class="submit-loading" style="display: none;">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Submitting...
                    </span>
                  </button>
                    <a href="{{ route('online-admissions.public-form') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset Form
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      (function(){
        const toggle = document.getElementById('transport_needed');
        const fields = document.querySelectorAll('.transport-field');
        const dropSelect = document.getElementById('drop_off_point_id');
        const dropOther = document.getElementById('drop_off_point_other');

        function syncFields() {
          const enabled = toggle?.checked;
          fields.forEach(f => f.style.display = enabled ? '' : 'none');
          if (!enabled) {
            if (dropSelect) dropSelect.value = '';
            if (dropOther) dropOther.value = '';
          }
          syncOther();
        }
        function syncOther() {
          if (!dropSelect || !dropOther) return;
          const showOther = dropSelect.value === 'other';
          dropOther.style.display = showOther ? '' : 'none';
          if (!showOther) dropOther.value = '';
        }
        toggle?.addEventListener('change', syncFields);
        dropSelect?.addEventListener('change', syncOther);
        syncFields();

        const classSelect = document.getElementById('preferred_classroom_id');
        const prevSection = document.getElementById('previous-school-section');
        const hint = document.getElementById('class-hint');
        function syncPrev() {
          if (!classSelect || !prevSection) return;
          const opt = classSelect.options[classSelect.selectedIndex];
          if (!opt || !opt.value) {
            prevSection.style.display = 'none';
            return;
          }
          const className = opt.textContent.trim().toLowerCase();
          // Show for PP2, Grade 1 to Grade 9
          const showPrev = /^pp2(\s|$)|^grade\s*[1-9](\s|$)/.test(className);
          prevSection.style.display = showPrev ? '' : 'none';
          
          if (hint) {
            const isCreche = opt?.getAttribute('data-creche') === '1';
            const isFoundation = opt?.getAttribute('data-foundation') === '1';
            if (isCreche) hint.textContent = 'Creche: below 2½ years. Final placement set by school.';
            else if (isFoundation) hint.textContent = 'Foundation: below 4 years. Final placement set by school.';
            else hint.textContent = 'Creche: below 2½ years. Foundation: below 4 years. Final placement set by school.';
          }
        }
        classSelect?.addEventListener('change', syncPrev);
        syncPrev();

        // Form validation and submission
        const form = document.getElementById('admissionForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitAddAnotherBtn = document.getElementById('submitAddAnotherBtn');
        const validationAlert = document.getElementById('validationAlert');
        const validationErrors = document.getElementById('validationErrors');
        let isSubmitting = false;

        function showValidationErrors(errors) {
          validationErrors.innerHTML = '';
          errors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            validationErrors.appendChild(li);
          });
          validationAlert.style.display = 'block';
          validationAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function hideValidationErrors() {
          validationAlert.style.display = 'none';
          validationErrors.innerHTML = '';
        }

        function validateForm() {
          hideValidationErrors();
          const errors = [];
          
          // Remove previous validation states
          form.querySelectorAll('.is-invalid').forEach(el => {
            if (!el.classList.contains('is-invalid-server')) {
              el.classList.remove('is-invalid');
            }
          });

          // Check required fields
          const firstName = form.querySelector('[name="first_name"]');
          const lastName = form.querySelector('[name="last_name"]');
          const dob = form.querySelector('[name="dob"]');
          const gender = form.querySelector('[name="gender"]');
          const residentialArea = form.querySelector('[name="residential_area"]');

          if (!firstName.value.trim()) {
            errors.push('First Name is required');
            firstName.classList.add('is-invalid');
          }
          
          if (!lastName.value.trim()) {
            errors.push('Last Name is required');
            lastName.classList.add('is-invalid');
          }
          
          if (!dob.value) {
            errors.push('Date of Birth is required');
            dob.classList.add('is-invalid');
          }
          
          if (!gender.value) {
            errors.push('Gender is required');
            gender.classList.add('is-invalid');
          }
          
          if (!residentialArea.value.trim()) {
            errors.push('Residential Area is required');
            residentialArea.classList.add('is-invalid');
          }

          // Check at least one parent contact
          const fatherName = form.querySelector('[name="father_name"]').value.trim();
          const motherName = form.querySelector('[name="mother_name"]').value.trim();
          const guardianName = form.querySelector('[name="guardian_name"]').value.trim();
          const fatherPhone = form.querySelector('[name="father_phone"]').value.trim();
          const motherPhone = form.querySelector('[name="mother_phone"]').value.trim();
          const guardianPhone = form.querySelector('[name="guardian_phone"]').value.trim();

          const hasParentName = fatherName || motherName || guardianName;
          const hasParentPhone = fatherPhone || motherPhone || guardianPhone;

          if (!hasParentName) {
            errors.push('At least one parent/guardian name is required (Father, Mother, or Guardian)');
          }
          
          if (!hasParentPhone) {
            errors.push('At least one parent/guardian phone number is required');
          }

          if (errors.length > 0) {
            showValidationErrors(errors);
            return false;
          }

          return true;
        }

        function setSubmitLoading(isLoading) {
          const buttons = [submitBtn, submitAddAnotherBtn];
          buttons.forEach(btn => {
            const textSpan = btn.querySelector('.submit-text');
            const loadingSpan = btn.querySelector('.submit-loading');
            if (isLoading) {
              btn.disabled = true;
              textSpan.style.display = 'none';
              loadingSpan.style.display = 'inline';
            } else {
              btn.disabled = false;
              textSpan.style.display = 'inline';
              loadingSpan.style.display = 'none';
            }
          });
        }

        form?.addEventListener('submit', function(e) {
          if (isSubmitting) {
            e.preventDefault();
            return false;
          }

          if (!validateForm()) {
            e.preventDefault();
            return false;
          }

          isSubmitting = true;
          setSubmitLoading(true);
        });

        // Reset loading state if user navigates back
        window.addEventListener('pageshow', function(event) {
          if (event.persisted) {
            isSubmitting = false;
            setSubmitLoading(false);
          }
        });
      })();
    </script>
</body>
</html>
