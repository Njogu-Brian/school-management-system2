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
    <title>Staff Registration - {{ $schoolName }}</title>
    <link rel="icon" href="{{ $faviconUrl }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    @php $hasVite = file_exists(public_path('build/manifest.json')); @endphp
    @if($hasVite)
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
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
            max-width: 880px;
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
            margin-top: 1.75rem;
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
        .form-control, .form-select { border-radius: 10px; }
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            border-radius: 10px;
            font-weight: 600;
        }
        .btn-primary:hover { filter: brightness(0.95); }
        .hp { position: absolute; left: -9999px; height: 0; overflow: hidden; }
        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <div class="mb-3">
                    <img src="{{ $logoUrl }}" alt="{{ $schoolName }} logo" style="max-height: 72px;">
                </div>
                <h1 class="mb-2"><i class="bi bi-person-badge" aria-hidden="true"></i> Staff Registration</h1>
                <p class="text-muted mb-1">{{ $schoolName }}</p>
                <p class="text-muted mb-0">Fill in your details. HR will review and send your staff login.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="status">
                    <i class="bi bi-check-circle" aria-hidden="true"></i> {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <h6 class="mb-2">Please correct the following:</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $open)
                <div class="alert alert-warning mb-0">Staff registration is currently closed. Please contact the school office.</div>
            @elseif (! session('success'))
            <form action="{{ route('staff.public-register.submit') }}" method="POST" novalidate>
                @csrf
                <div class="hp" aria-hidden="true">
                    <label>Company website</label>
                    <input type="text" name="company_website" tabindex="-1" autocomplete="off">
                </div>

                <h5 class="section-title"><i class="bi bi-person" aria-hidden="true"></i> Personal details</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required-field" for="first_name">First name</label>
                        <input id="first_name" type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="middle_name">Middle name</label>
                        <input id="middle_name" type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field" for="last_name">Last name</label>
                        <input id="last_name" type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field" for="date_of_birth">Date of birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field" for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">Select</option>
                            <option value="Female" @selected(old('gender')==='Female')>Female</option>
                            <option value="Male" @selected(old('gender')==='Male')>Male</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field" for="marital_status">Marital status</label>
                        <select id="marital_status" name="marital_status" class="form-select @error('marital_status') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach (['Single','Married','Divorced','Widowed'] as $status)
                                <option value="{{ $status }}" @selected(old('marital_status')===$status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required-field" for="id_number">National ID</label>
                        <input id="id_number" type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror" value="{{ old('id_number') }}" required inputmode="numeric">
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-telephone" aria-hidden="true"></i> Contact</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required-field" for="personal_email">Personal email</label>
                        <input id="personal_email" type="email" name="personal_email" class="form-control @error('personal_email') is-invalid @enderror" value="{{ old('personal_email') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required-field" for="phone_number">Phone</label>
                        <input id="phone_number" type="tel" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}" required placeholder="07xxxxxxxx">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="emergency_contact_phone">Alternative phone</label>
                        <input id="emergency_contact_phone" type="tel" name="emergency_contact_phone" class="form-control" value="{{ old('emergency_contact_phone') }}" placeholder="07xxxxxxxx">
                    </div>
                </div>

                <h5 class="section-title"><i class="bi bi-bank" aria-hidden="true"></i> Payroll (optional)</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="kra_pin">KRA PIN</label>
                        <input id="kra_pin" type="text" name="kra_pin" class="form-control" value="{{ old('kra_pin') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nssf">NSSF</label>
                        <input id="nssf" type="text" name="nssf" class="form-control" value="{{ old('nssf') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nhif">SHIF / NHIF</label>
                        <input id="nhif" type="text" name="nhif" class="form-control" value="{{ old('nhif') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_name">Bank name</label>
                        <input id="bank_name" type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_branch">Bank branch</label>
                        <input id="bank_branch" type="text" name="bank_branch" class="form-control" value="{{ old('bank_branch') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="bank_account">Account number</label>
                        <input id="bank_account" type="text" name="bank_account" class="form-control" value="{{ old('bank_account') }}" inputmode="numeric">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Submit registration
                    </button>
                </div>
                <p class="text-muted small text-center mt-3 mb-0">Required fields are marked with <span class="text-danger">*</span></p>
            </form>
            @endif
        </div>
    </div>
</body>
</html>
