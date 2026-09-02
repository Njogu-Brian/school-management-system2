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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --brand-primary: {{ setting('primary_color', setting('finance_primary_color', '#390754')) }};
            --brand-accent: {{ setting('secondary_color', setting('finance_secondary_color', '#5b2a7a')) }};
            --brand-bg: #f3f5f8;
            --brand-surface: #ffffff;
            --brand-border: #e2e8f0;
            --brand-text: #0f172a;
            --brand-muted: #64748b;
            --brand-success: #059669;
            --radius: 16px;
            --shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }
        * { box-sizing: border-box; }
        html {
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }
        body {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(1200px 400px at 10% -10%, color-mix(in srgb, var(--brand-primary) 18%, transparent), transparent),
                radial-gradient(900px 320px at 100% 0%, color-mix(in srgb, var(--brand-accent) 14%, transparent), transparent),
                var(--brand-bg);
            color: var(--brand-text);
            min-height: 100dvh;
            margin: 0;
            padding:
                max(12px, env(safe-area-inset-top))
                max(12px, env(safe-area-inset-right))
                max(20px, env(safe-area-inset-bottom))
                max(12px, env(safe-area-inset-left));
        }
        .page-wrapper {
            width: 100%;
            max-width: 1180px;
            margin: 0 auto;
        }
        .hero {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
            color: #fff;
            border-radius: calc(var(--radius) + 4px);
            padding: 22px 20px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 14px;
        }
        .hero__brand {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
            flex: 1 1 auto;
        }
        .hero__logo {
            width: 52px;
            height: 52px;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(255,255,255,0.15);
            padding: 8px;
            flex-shrink: 0;
        }
        .hero__title {
            font-size: clamp(1.15rem, 2.5vw, 1.45rem);
            margin-bottom: 0;
        }
        .hero__meta {
            font-size: 0.82rem;
            line-height: 1.45;
            opacity: 0.9;
        }
        .hero__contact span {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 2px;
        }
        .hero::after {
            content: '';
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
        }
        .form-shell {
            background: var(--brand-surface);
            border: 1px solid var(--brand-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: clamp(14px, 2.5vw, 28px);
        }
        .intro-note {
            background: #f8fafc;
            border: 1px solid var(--brand-border);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 0.92rem;
            line-height: 1.55;
            color: var(--brand-muted);
        }
        .intro-note strong { color: var(--brand-text); }
        .form-section {
            border: 1px solid var(--brand-border);
            border-radius: 14px;
            padding: 18px;
            background: #fff;
            margin-bottom: 16px;
        }
        .form-section + .form-section { margin-top: 4px; }
        .section-header {
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--brand-muted);
            margin-bottom: 12px;
        }
        .badge-pill {
            border-radius: 999px;
            padding: 6px 12px;
            background: rgba(255,255,255,0.18);
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .form-label {
            font-weight: 600;
            color: var(--brand-text);
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .form-control, .form-select {
            border-radius: 12px;
            border-color: var(--brand-border);
            padding: 0.7rem 0.9rem;
            min-height: 46px;
            font-size: 16px;
        }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.12);
        }
        .field-error-hint {
            color: #b42318;
            font-size: 0.82rem;
            margin-top: 6px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--brand-primary) 18%, transparent);
        }
        .btn-primary {
            background: var(--brand-primary);
            border-color: var(--brand-primary);
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--brand-accent);
            border-color: var(--brand-accent);
        }
        .upload-hint {
            font-size: 0.85rem;
            color: var(--brand-muted);
        }
        .file-upload-wrapper { position: relative; }
        .file-upload-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        .file-upload-btn {
            flex: 1 1 30%;
            min-width: 96px;
            padding: 12px 8px;
            border: 1.5px dashed var(--brand-border);
            border-radius: 12px;
            background: #f8fafc;
            cursor: pointer;
            text-align: center;
            transition: border-color 0.15s, background 0.15s;
            font-size: 0.85rem;
            color: var(--brand-text);
            margin-bottom: 0;
        }
        .file-upload-btn:hover {
            border-color: var(--brand-primary);
            background: color-mix(in srgb, var(--brand-primary) 6%, #fff);
        }
        .file-upload-btn i {
            display: block;
            font-size: 1.4rem;
            margin-bottom: 4px;
            color: var(--brand-primary);
        }
        .file-input-hidden {
            position: absolute;
            left: 0;
            top: 0;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }
        .file-preview, .existing-docs {
            margin-top: 8px;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid var(--brand-border);
            border-radius: 10px;
            font-size: 0.85rem;
        }
        .existing-docs {
            background: color-mix(in srgb, var(--brand-primary) 6%, #fff);
        }
        .existing-docs a {
            color: var(--brand-primary);
            text-decoration: none;
            margin-right: 12px;
            font-weight: 600;
        }
        .existing-docs a:hover { text-decoration: underline; }
        .alert {
            border-radius: 12px;
            border: none;
        }
        .feedback-alert {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .feedback-alert:last-child { margin-bottom: 0; }
        .feedback-alert__icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
            font-size: 0.9rem;
        }
        .alert-success .feedback-alert__icon { background: rgba(5, 150, 105, 0.15); color: #047857; }
        .alert-warning .feedback-alert__icon { background: rgba(217, 119, 6, 0.15); color: #b45309; }
        .alert-danger .feedback-alert__icon { background: rgba(220, 53, 69, 0.15); color: #b42318; }
        .feedback-alert__body p:last-child { margin-bottom: 0; }
        .feedback-error-list {
            padding-left: 1.1rem;
            margin-bottom: 0;
        }
        .feedback-error-list li { margin-bottom: 6px; }
        .feedback-error-list li:last-child { margin-bottom: 0; }
        .feedback-error-label { font-weight: 600; }
        .phone-input-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0;
        }
        .phone-input-group .phone-flag {
            display: none;
        }
        .phone-input-group .phone-code-select {
            max-width: 140px;
            flex: 0 0 auto;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .phone-input-group .phone-input {
            flex: 1 1 160px;
            min-width: 0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .sticky-actions {
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, transparent, var(--brand-surface) 24%);
            padding: 16px 0 max(8px, env(safe-area-inset-bottom));
            margin-top: 12px;
            z-index: 5;
        }
        .student-section-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }
        @media (min-width: 576px) {
            body { padding: 20px 24px 32px; }
            .hero {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                padding: 24px 26px;
            }
            .hero__logo { width: 56px; height: 56px; }
            .phone-input-group .phone-flag { display: flex; }
        }
        @media (min-width: 992px) {
            .page-wrapper { max-width: 1240px; }
            .form-section { padding: 22px; }
        }
        @media (max-width: 575.98px) {
            .hero { border-radius: 14px; }
            .form-shell { border-radius: 14px; }
            .form-section { padding: 14px; }
            .file-upload-btn { flex: 1 1 100%; }
            .phone-input-group .phone-code-select {
                max-width: 100%;
                flex: 1 1 100%;
                border-radius: 12px 12px 0 0;
            }
            .phone-input-group .phone-input {
                flex: 1 1 100%;
                border-radius: 0 0 12px 12px;
            }
            .badge-pill { align-self: flex-start; }
        }
    </style>
</head>
<body>
@php
    $schoolName = setting('school_name') ?? $appName ?? config('app.name', 'School Management System');
    $schoolPhone = setting('school_phone') ?? null;
    $schoolEmail = setting('school_email') ?? null;
    $schoolAddress = setting('school_address') ?? null;
@endphp
<div class="page-wrapper">
            <div class="hero mb-3">
                <div class="hero__brand">
                    <img src="{{ $logoUrl }}" alt="{{ $schoolName }} logo" class="hero__logo">
                    <div class="min-w-0">
                        <div class="fw-semibold text-uppercase small" style="letter-spacing:0.6px;">{{ $schoolName }}</div>
                        <h1 class="hero__title">Update Student Details</h1>
                        <div class="hero__meta">
                            {{ $family ? 'Family reference: '.$family->id : 'Student profile update' }}
                            @if($schoolPhone || $schoolEmail || $schoolAddress)
                                <div class="hero__contact mt-1">
                                    @if($schoolPhone)
                                        <span><strong>Tel:</strong> {{ $schoolPhone }}</span>
                                    @endif
                                    @if($schoolEmail)
                                        <span><strong>Email:</strong> {{ $schoolEmail }}</span>
                                    @endif
                                    @if($schoolAddress)
                                        <span class="d-block"><strong>Address:</strong> {{ $schoolAddress }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <span class="badge-pill"><i class="bi bi-shield-lock me-1"></i> Secure link</span>
            </div>

            <div class="form-shell">
                @include('family_update.partials.feedback')

                <form action="{{ route('family-update.submit', $link->token) }}" method="POST" enctype="multipart/form-data" novalidate id="familyUpdateForm">
                    @csrf
                    <div class="intro-note mb-4">
                        <strong>How this form works</strong><br>
                        Fill in your child’s details as they appear on the birth certificate. You can <strong>save your progress at any time</strong> — if something is still missing, we will tell you exactly what to complete next.
                        Parent/guardian details are shared for siblings and only need to be filled once.
                        Complete <strong>all father details or all mother details</strong> (ID upload is optional).
                        Fields marked <span class="text-danger">*</span> are required for that section to save.
                    </div>
                    @foreach($students as $stu)
                        <div class="student-section-header">
                            <h2 class="section-header text-uppercase mb-0 h6">Student Details</h2>
                            <span class="badge bg-light text-dark border">Admission #{{ $stu->admission_number }}</span>
                        </div>
                        <div class="form-section mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">{{ $stu->full_name }}</div>
                            </div>
                            <input type="hidden" name="students[{{ $stu->id }}][id]" value="{{ $stu->id }}">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="students[{{ $stu->id }}][first_name]" class="form-control" value="{{ old('students.'.$stu->id.'.first_name', $stu->first_name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" name="students[{{ $stu->id }}][middle_name]" class="form-control" value="{{ old('students.'.$stu->id.'.middle_name', $stu->middle_name) }}" placeholder="Leave blank if none">
                                    <small class="text-muted">Optional — leave blank if the child has no middle name.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="students[{{ $stu->id }}][last_name]" class="form-control" value="{{ old('students.'.$stu->id.'.last_name', $stu->last_name) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                                    <select name="students[{{ $stu->id }}][gender]" class="form-select" required>
                                        @php 
                                            $currentGender = old('students.'.$stu->id.'.gender', ucfirst(strtolower($stu->gender ?? '')));
                                        @endphp
                                        <option value="Male" @selected($currentGender=='Male')>Male</option>
                                        <option value="Female" @selected($currentGender=='Female')>Female</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                    <input type="date" name="students[{{ $stu->id }}][dob]" class="form-control" value="{{ old('students.'.$stu->id.'.dob', $stu->dob ? ($stu->dob instanceof \Carbon\Carbon ? $stu->dob->format('Y-m-d') : \Carbon\Carbon::parse($stu->dob)->format('Y-m-d')) : '') }}" required>
                                    @if($stu->dob)
                                        <small class="text-muted d-block mt-1">Current: {{ $stu->dob instanceof \Carbon\Carbon ? $stu->dob->format('M d, Y') : \Carbon\Carbon::parse($stu->dob)->format('M d, Y') }}</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Classroom (view only)</label>
                                    <input type="text" class="form-control" value="{{ $stu->classroom->name ?? '—' }}" disabled>
                                </div>
                                @include('students.partials.kemis_learner_fields', [
                                    'student' => $stu,
                                    'htmlPrefix' => 'students['.$stu->id.']',
                                    'oldPrefix' => 'students.'.$stu->id,
                                    'fieldIdSuffix' => $stu->id,
                                ])
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
                                    @include('family_update.partials.media_picker', [
                                        'name' => 'students['.$stu->id.'][passport_photo]',
                                        'id' => 'passport_photo_'.$stu->id,
                                        'kind' => 'image',
                                        'hint' => 'Choose a photo from your gallery or take one now. Large photos are compressed automatically (max 8 MB).',
                                    ])
                                    @php
                                        $passportDocs = $stu->documents()->where(function($q) {
                                            $q->where('category', 'student_profile_photo')
                                              ->orWhere('document_type', 'photo');
                                        })->latest()->get();
                                    @endphp
                                    @if($passportDocs->isNotEmpty() || $stu->photo_path)
                                        <div class="existing-docs mt-2">
                                            <strong>Existing photos:</strong><br>
                                            @foreach($passportDocs as $doc)
                                                <a href="{{ $doc->file_url }}" target="_blank">
                                                    <i class="bi bi-file-earmark-image"></i> {{ $doc->title }} ({{ $doc->created_at->format('M d, Y') }})
                                                </a>
                                            @endforeach
                                            @if($stu->photo_path && !$passportDocs->where('file_path', $stu->photo_path)->first())
                                                <a href="{{ route('family-update.files.preview', [$link->token, 'student', $stu->id, 'photo_path']) }}" target="_blank">
                                                    <i class="bi bi-image"></i> Legacy Photo
                                                </a>
                                                <a href="{{ route('family-update.files.download', [$link->token, 'student', $stu->id, 'photo_path']) }}" target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Birth Certificate / Notification</label>
                                    @include('family_update.partials.media_picker', [
                                        'name' => 'students['.$stu->id.'][birth_certificate]',
                                        'id' => 'birth_cert_'.$stu->id,
                                        'kind' => 'document',
                                        'hint' => 'Browse Files/Downloads for a PDF, or choose/take a photo. PDF/JPG/PNG up to 10 MB. Photos are compressed automatically.',
                                    ])
                                    @php
                                        $birthCertDocs = $stu->documents()->where(function($q) {
                                            $q->where('category', 'student_birth_certificate')
                                              ->orWhere('document_type', 'birth_certificate');
                                        })->latest()->get();
                                    @endphp
                                    @if($birthCertDocs->isNotEmpty() || $stu->birth_certificate_path)
                                        <div class="existing-docs mt-2">
                                            <strong>Existing certificates:</strong><br>
                                            @foreach($birthCertDocs as $doc)
                                                <a href="{{ $doc->file_url }}" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> {{ $doc->title }} ({{ $doc->created_at->format('M d, Y') }})
                                                </a>
                                            @endforeach
                                            @if($stu->birth_certificate_path && !$birthCertDocs->where('file_path', $stu->birth_certificate_path)->first())
                                                <a href="{{ route('family-update.files.preview', [$link->token, 'student', $stu->id, 'birth_certificate_path']) }}" target="_blank">
                                                    <i class="bi bi-file-earmark"></i> Legacy Certificate
                                                </a>
                                                <a href="{{ route('family-update.files.download', [$link->token, 'student', $stu->id, 'birth_certificate_path']) }}" target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                </div>
                            </div>
                        @endforeach

                        <h6 class="text-uppercase text-muted mb-3">Parent / Guardian</h6>
                        @if($students->count() > 1)
                            <p class="text-muted small">These details apply to all {{ $students->count() }} children on this form and are saved once.</p>
                        @endif
                        <div class="alert alert-info py-2 small">Complete <strong>all father details or all mother details</strong> (names, ID type, ID number, country of residence, phone, WhatsApp, and email). ID document upload is optional. Guardian is optional.</div>
                        <div class="row g-3 mb-4">
                            @php $parent = $students->first()->parent ?? null; @endphp
                            <div class="col-md-6">
                                <label class="form-label">Marital Status</label>
                                <select name="marital_status" class="form-select">
                                    <option value="">Select</option>
                                    <option value="married" @selected(old('marital_status', $parent->marital_status ?? '')=='married')>Married</option>
                                    <option value="single_parent" @selected(old('marital_status', $parent->marital_status ?? '')=='single_parent')>Single Parent</option>
                                    <option value="co_parenting" @selected(old('marital_status', $parent->marital_status ?? '')=='co_parenting')>Co-parenting</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">School notifications (SMS / email / WhatsApp)</label>
                                @php $pubParent = $students->first()?->parent; $pubMute = $pubParent?->school_notifications_muted_parent; @endphp
                                <select name="school_notifications_muted_parent" class="form-select">
                                    <option value="" @selected(old('school_notifications_muted_parent', $pubMute ?? '') === '' || old('school_notifications_muted_parent', $pubMute) === null)>Both parents</option>
                                    <option value="father" @selected(old('school_notifications_muted_parent', $pubMute) === 'father')>Do not notify father (mother only)</option>
                                    <option value="mother" @selected(old('school_notifications_muted_parent', $pubMute) === 'mother')>Do not notify mother (father only)</option>
                                </select>
                                <small class="text-muted d-block mt-1">Only one parent can be excluded. The other must have at least one contact on file.</small>
                            </div>
                            @include('students.partials.kemis_parent_identity_fields', ['slot' => 'father', 'parent' => $parent, 'title' => 'Father'])
                                <div class="col-md-6">
                                    <label class="form-label">Father Phone</label>
                                    @php
                                        $fatherPhone = old('father_phone', $students->first()->parent->father_phone ?? '');
                                        $fatherCountryCode = old('father_phone_country_code', $students->first()->parent->father_phone_country_code ?? '+254');
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
                                        $fatherWhatsapp = old('father_whatsapp', $students->first()->parent->father_whatsapp ?? '');
                                        $fatherWhatsappCountryCode = old('father_whatsapp_country_code', $students->first()->parent->father_whatsapp_country_code ?? $fatherCountryCode);
                                        // Normalize +KE to +254
                                        $fatherWhatsappCountryCode = strtolower($fatherWhatsappCountryCode) === '+ke' || strtolower($fatherWhatsappCountryCode) === 'ke' ? '+254' : $fatherWhatsappCountryCode;
                                        $fatherWhatsappLocal = extract_local_phone($fatherWhatsapp, $fatherWhatsappCountryCode);
                                    @endphp
                                    <div class="input-group phone-input-group">
                                        <span class="input-group-text phone-flag" id="father_whatsapp_prefix">+254</span>
                                        <select name="father_whatsapp_country_code" class="form-select flex-grow-0 phone-code-select" data-target="father_whatsapp" style="max-width:170px">
                                            @foreach($countryCodes as $code => $label)
                                                <option value="{{ $code }}" @selected($fatherWhatsappCountryCode==$code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="father_whatsapp" id="father_whatsapp" class="form-control phone-input" value="{{ $fatherWhatsappLocal }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="father_whatsapp_help">
                                    </div>
                                    <small class="upload-hint d-block" id="father_whatsapp_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father Email</label>
                                <input type="email" name="father_email" class="form-control" value="{{ old('father_email', $students->first()->parent->father_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Father ID Document</label>
                                @include('family_update.partials.media_picker', [
                                    'name' => 'father_id_document',
                                    'id' => 'father_id_document',
                                    'kind' => 'document',
                                    'hint' => 'Browse Files/Downloads for a PDF, or choose/take a photo. PDF/JPG/PNG up to 10 MB. Photos are compressed automatically.',
                                ])
                                    @php
                                        $parent = $students->first()->parent ?? null;
                                        $fatherIdDocs = $parent ? $parent->documents()->where(function($q) {
                                            $q->where('category', 'parent_id_card')
                                              ->orWhere('document_type', 'id_card');
                                        })->where(function($q) {
                                            $q->where('title', 'like', '%father%')
                                              ->orWhere('title', 'like', '%Father%');
                                        })->latest()->get() : collect();
                                    @endphp
                                    @if($fatherIdDocs->isNotEmpty() || optional($parent)->father_id_document)
                                        <div class="existing-docs mt-2">
                                            <strong>Existing documents:</strong><br>
                                            @foreach($fatherIdDocs as $doc)
                                                <a href="{{ $doc->file_url }}" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> {{ $doc->title }} ({{ $doc->created_at->format('M d, Y') }})
                                                </a>
                                            @endforeach
                                            @if(optional($parent)->father_id_document && !$fatherIdDocs->where('file_path', $parent->father_id_document)->first())
                                                <a href="{{ route('family-update.files.preview', [$link->token, 'parent', $parent->id, 'father_id_document']) }}" target="_blank">
                                                    <i class="bi bi-file-earmark"></i> Legacy Document
                                                </a>
                                                <a href="{{ route('family-update.files.download', [$link->token, 'parent', $parent->id, 'father_id_document']) }}" target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                            </div>

                            @include('students.partials.kemis_parent_identity_fields', ['slot' => 'mother', 'parent' => $parent, 'title' => 'Mother'])
                                <div class="col-md-6">
                                    <label class="form-label">Mother Phone</label>
                                    @php
                                        $motherPhone = old('mother_phone', $students->first()->parent->mother_phone ?? '');
                                        $motherCountryCode = old('mother_phone_country_code', $students->first()->parent->mother_phone_country_code ?? '+254');
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
                                        $motherWhatsapp = old('mother_whatsapp', $students->first()->parent->mother_whatsapp ?? '');
                                        $motherWhatsappCountryCode = old('mother_whatsapp_country_code', $students->first()->parent->mother_whatsapp_country_code ?? $motherCountryCode);
                                        // Normalize +KE to +254
                                        $motherWhatsappCountryCode = strtolower($motherWhatsappCountryCode) === '+ke' || strtolower($motherWhatsappCountryCode) === 'ke' ? '+254' : $motherWhatsappCountryCode;
                                        $motherWhatsappLocal = extract_local_phone($motherWhatsapp, $motherWhatsappCountryCode);
                                    @endphp
                                    <div class="input-group phone-input-group">
                                        <span class="input-group-text phone-flag" id="mother_whatsapp_prefix">+254</span>
                                        <select name="mother_whatsapp_country_code" class="form-select flex-grow-0 phone-code-select" data-target="mother_whatsapp" style="max-width:170px">
                                            @foreach($countryCodes as $code => $label)
                                                <option value="{{ $code }}" @selected($motherWhatsappCountryCode==$code)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="mother_whatsapp" id="mother_whatsapp" class="form-control phone-input" value="{{ $motherWhatsappLocal }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="mother_whatsapp_help">
                                    </div>
                                    <small class="upload-hint d-block" id="mother_whatsapp_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother Email</label>
                                <input type="email" name="mother_email" class="form-control" value="{{ old('mother_email', $students->first()->parent->mother_email ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mother ID Document</label>
                                @include('family_update.partials.media_picker', [
                                    'name' => 'mother_id_document',
                                    'id' => 'mother_id_document',
                                    'kind' => 'document',
                                    'hint' => 'Browse Files/Downloads for a PDF, or choose/take a photo. PDF/JPG/PNG up to 10 MB. Photos are compressed automatically.',
                                ])
                                    @php
                                        $parent = $students->first()->parent ?? null;
                                        $motherIdDocs = $parent ? $parent->documents()->where(function($q) {
                                            $q->where('category', 'parent_id_card')
                                              ->orWhere('document_type', 'id_card');
                                        })->where(function($q) {
                                            $q->where('title', 'like', '%mother%')
                                              ->orWhere('title', 'like', '%Mother%');
                                        })->latest()->get() : collect();
                                    @endphp
                                    @if($motherIdDocs->isNotEmpty() || optional($parent)->mother_id_document)
                                        <div class="existing-docs mt-2">
                                            <strong>Existing documents:</strong><br>
                                            @foreach($motherIdDocs as $doc)
                                                <a href="{{ $doc->file_url }}" target="_blank">
                                                    <i class="bi bi-file-earmark-pdf"></i> {{ $doc->title }} ({{ $doc->created_at->format('M d, Y') }})
                                                </a>
                                            @endforeach
                                            @if(optional($parent)->mother_id_document && !$motherIdDocs->where('file_path', $parent->mother_id_document)->first())
                                                <a href="{{ route('family-update.files.preview', [$link->token, 'parent', $parent->id, 'mother_id_document']) }}" target="_blank">
                                                    <i class="bi bi-file-earmark"></i> Legacy Document
                                                </a>
                                                <a href="{{ route('family-update.files.download', [$link->token, 'parent', $parent->id, 'mother_id_document']) }}" target="_blank">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                            </div>

                            @include('students.partials.kemis_parent_identity_fields', ['slot' => 'guardian', 'parent' => $parent, 'title' => 'Guardian (if there are no parents)', 'showRelationship' => true])
                            <div class="col-md-6">
                                <label class="form-label">Guardian Email</label>
                                <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email', $parent->guardian_email ?? '') }}">
                            </div>
                                <div class="col-md-6">
                                    <label class="form-label">Guardian Phone</label>
                                    @php
                                        $guardianPhone = old('guardian_phone', $students->first()->parent->guardian_phone ?? '');
                                        $guardianCountryCode = old('guardian_phone_country_code', $students->first()->parent->guardian_phone_country_code ?? '+254');
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
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Emergency & Medical</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Name <span class="text-danger">*</span></label>
                                <small class="text-muted d-block mb-1">Person we call if parents/guardians cannot be reached.</small>
                                <input type="text" name="emergency_contact_name" class="form-control" value="{{ old('emergency_contact_name', $students->first()->emergency_contact_name ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emergency Contact Phone <span class="text-danger">*</span></label>
                                @php
                                    $emergencyPhone = old('emergency_contact_phone', $students->first()->emergency_contact_phone ?? '');
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
                                    <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" class="form-control phone-input" value="{{ $emergencyLocalPhone }}" placeholder="7XXXXXXXX" inputmode="numeric" pattern="(7|1)[0-9]{8}" aria-describedby="emergency_phone_help" required>
                                </div>
                                <small class="upload-hint d-block" id="emergency_phone_help">Kenyan format: 7/1 + 8 digits. Other countries: 6-12 digits.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Preferred Hospital / Medical Facility <span class="text-danger">*</span></label>
                                <input type="text" name="preferred_hospital" class="form-control" value="{{ old('preferred_hospital', $students->first()->preferred_hospital ?? '') }}" required>
                            </div>
                        </div>

                        <h6 class="text-uppercase text-muted mb-3">Residential</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Residential Area <span class="text-danger">*</span></label>
                                <input type="text" name="residential_area" class="form-control" value="{{ old('residential_area', $students->first()->residential_area ?? '') }}" required>
                            </div>
                        </div>

                        <div class="sticky-actions">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <span class="submit-text">Save updates</span>
                                    <span class="submit-loading" style="display:none">Saving…</span>
                                </button>
                                <div class="text-muted small text-center">You can revisit this link anytime to make further updates.</div>
                            </div>
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

    function fileIsAllowed(file, kind) {
        const name = (file.name || '').toLowerCase();
        const type = (file.type || '').toLowerCase();
        if (kind === 'image') {
            return type.startsWith('image/') || /\.(jpe?g|png|webp|heic|heif)$/i.test(name);
        }
        return type === 'application/pdf'
            || type.startsWith('image/')
            || /\.(pdf|jpe?g|png|webp)$/i.test(name);
    }

    function compressImageFile(file, maxBytes) {
        return new Promise(function (resolve) {
            const type = (file.type || '').toLowerCase();
            if (!type.startsWith('image/') || type.indexOf('heic') !== -1 || type.indexOf('heif') !== -1) {
                resolve(file);
                return;
            }
            const img = new Image();
            const url = URL.createObjectURL(file);
            img.onload = function () {
                URL.revokeObjectURL(url);
                let width = img.width;
                let height = img.height;
                const maxDim = 1600;
                if (width > maxDim || height > maxDim) {
                    const scale = maxDim / Math.max(width, height);
                    width = Math.round(width * scale);
                    height = Math.round(height * scale);
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    resolve(file);
                    return;
                }
                ctx.drawImage(img, 0, 0, width, height);
                let quality = 0.82;
                const finish = function (blob) {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    if (blob.size > maxBytes && quality > 0.5) {
                        quality -= 0.12;
                        canvas.toBlob(finish, 'image/jpeg', quality);
                        return;
                    }
                    const name = (file.name || 'photo').replace(/\.[^.]+$/, '.jpg');
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                };
                canvas.toBlob(finish, 'image/jpeg', quality);
            };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                resolve(file);
            };
            img.src = url;
        });
    }

    document.querySelectorAll('[data-media-picker]').forEach(function (wrap) {
        const canonical = wrap.querySelector('.js-media-canonical');
        const preview = wrap.querySelector('.js-media-preview');
        const kind = wrap.dataset.mediaPicker || 'document';
        if (!canonical) return;

        canonical.dataset.fieldName = canonical.getAttribute('name') || canonical.dataset.fieldName || '';

        const filesInput = wrap.querySelector('[data-media-source="files"]');
        if (filesInput && /Android/i.test(navigator.userAgent)) {
            filesInput.setAttribute('accept', '*/*');
        }

        function assignCanonicalFile(file) {
            const fieldName = canonical.dataset.fieldName;
            wrap.querySelectorAll('.js-media-source').forEach(function (el) {
                el.removeAttribute('name');
            });
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                canonical.files = dt.files;
                if (canonical.files && canonical.files.length) {
                    canonical.setAttribute('name', fieldName);
                    return true;
                }
            } catch (err) {
                // Some mobile browsers cannot copy FileList; submit the source input instead.
            }
            return false;
        }

        wrap.querySelectorAll('.js-media-source').forEach(function (source) {
            source.addEventListener('change', function () {
                const file = source.files && source.files[0];
                if (!file) {
                    return;
                }
                if (!fileIsAllowed(file, kind)) {
                    source.value = '';
                    source.removeAttribute('name');
                    canonical.value = '';
                    canonical.setAttribute('name', canonical.dataset.fieldName);
                    if (preview) {
                        preview.style.display = 'block';
                        preview.innerHTML = '<span class="text-danger">Please choose a ' + (kind === 'image' ? 'JPG or PNG photo' : 'PDF, JPG, or PNG') + '.</span>';
                    }
                    return;
                }
                const showPreview = function (readyFile) {
                    if (preview) {
                        preview.style.display = 'block';
                        const icon = (readyFile.type === 'application/pdf' || /\.pdf$/i.test(readyFile.name)) ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image';
                        preview.innerHTML = '<i class="bi ' + icon + '"></i> Selected: ' + readyFile.name + ' (' + (readyFile.size / 1024).toFixed(1) + ' KB)';
                    }
                };
                if ((file.type || '').startsWith('image/')) {
                    if (preview) {
                        preview.style.display = 'block';
                        preview.innerHTML = '<span class="text-muted">Compressing photo…</span>';
                    }
                    compressImageFile(file, 3.5 * 1024 * 1024).then(function (readyFile) {
                        if (!assignCanonicalFile(readyFile)) {
                            source.setAttribute('name', canonical.dataset.fieldName);
                            canonical.removeAttribute('name');
                            showPreview(file);
                            return;
                        }
                        showPreview(readyFile);
                    });
                    return;
                }
                if (!assignCanonicalFile(file)) {
                    canonical.removeAttribute('name');
                    source.setAttribute('name', canonical.dataset.fieldName);
                }
                showPreview(file);
            });
        });
    });
    
    // Form submission handling with loading state
    const form = document.getElementById('familyUpdateForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form && submitBtn) {
        const submitText = submitBtn.querySelector('.submit-text');
        const submitLoading = submitBtn.querySelector('.submit-loading');
        
        form.addEventListener('submit', function(e) {
            // Show loading state
            submitBtn.disabled = true;
            if (submitText) submitText.style.display = 'none';
            if (submitLoading) submitLoading.style.display = 'inline';
            
            // Log form submission for debugging
            console.log('Family Update Form: Submitting...', {
                students_count: document.querySelectorAll('input[name^="students["][name$="[id]"]').length,
                form_action: form.action
            });
        });
    }
</script>
</body>
</html>
