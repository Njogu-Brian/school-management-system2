<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $settings = \App\Models\Setting::whereIn('key', [
            'school_name',
            'school_logo',
            'favicon',
            'school_email',
            'school_phone',
            'school_address',
        ])->pluck('value', 'key');

        $schoolName = $settings['school_name'] ?? 'Royal Kings School';
        $schoolEmail = $settings['school_email'] ?? 'info@royalkingsschools.sc.ke';
        $schoolPhone = $settings['school_phone'] ?? '+254 719 396 233';
        $schoolAddress = $settings['school_address'] ?? 'Wangige, Nairobi, Kenya';
        $logoSetting = $settings['school_logo'] ?? null;
        $faviconSetting = $settings['favicon'] ?? $logoSetting;

        $resolveImage = function ($filename) {
            if (! $filename) {
                return null;
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filename)) {
                return \Illuminate\Support\Facades\Storage::url($filename);
            }
            if (function_exists('public_images_path') && file_exists(public_images_path($filename))) {
                return public_image_url($filename);
            }
            if (file_exists(public_path('images/'.$filename))) {
                return asset('images/'.$filename);
            }

            return null;
        };

        $logoUrl = $resolveImage($logoSetting);
        $faviconUrl = $resolveImage($faviconSetting) ?? $logoUrl;
        $effectiveDate = '24 July 2026';
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Terms of Use for {{ $schoolName }} school management systems and mobile apps, including Royal Kings Admin and Royal Kings Users.">
    <title>Terms of Use — {{ $schoolName }}</title>
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #0f1f33;
            --muted: #5a6b7d;
            --line: #d7e0ea;
            --paper: #ffffff;
            --sky: #f3f7fb;
            --brand: #004a99;
            --brand-deep: #003366;
            --accent: #c9a227;
            --shadow: 0 18px 50px rgba(15, 31, 51, 0.12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            font-family: "DM Sans", system-ui, sans-serif;
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(0, 74, 153, 0.18), transparent 55%),
                radial-gradient(900px 420px at 100% 0%, rgba(201, 162, 39, 0.16), transparent 50%),
                linear-gradient(180deg, #e8f0f8 0%, var(--sky) 40%, #eef3f8 100%);
        }
        .page { max-width: 820px; margin: 0 auto; padding: 2rem 1.25rem 3.5rem; }
        .hero {
            background: linear-gradient(145deg, var(--brand-deep) 0%, var(--brand) 55%, #1a6fc4 100%);
            color: #fff;
            border-radius: 22px;
            padding: 2rem 1.75rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(201, 162, 39, 0.22);
        }
        .brand-row { display: flex; align-items: center; gap: 1rem; position: relative; z-index: 1; }
        .brand-row img {
            width: 64px; height: 64px; object-fit: contain;
            background: rgba(255,255,255,0.95); border-radius: 14px; padding: 6px;
        }
        .brand-mark {
            width: 64px; height: 64px; border-radius: 14px; display: grid; place-items: center;
            background: rgba(255,255,255,0.15); font-family: Fraunces, Georgia, serif;
            font-weight: 700; font-size: 1.35rem;
        }
        .brand-text small {
            display: block; opacity: 0.85; font-size: 0.85rem;
            letter-spacing: 0.04em; text-transform: uppercase; margin-bottom: 0.2rem;
        }
        .brand-text strong { font-family: Fraunces, Georgia, serif; font-size: 1.35rem; font-weight: 700; }
        .hero h1 {
            position: relative; z-index: 1;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.85rem, 4vw, 2.45rem);
            line-height: 1.15; margin: 1.35rem 0 0.65rem; font-weight: 700;
        }
        .hero p {
            position: relative; z-index: 1; margin: 0; max-width: 38rem;
            color: rgba(255,255,255,0.9); font-size: 1.02rem; line-height: 1.55;
        }
        .meta { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 1.25rem; position: relative; z-index: 1; }
        .chip {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.4rem 0.75rem; border-radius: 999px;
            background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); font-size: 0.85rem;
        }
        .card {
            margin-top: 1.25rem; background: var(--paper); border: 1px solid var(--line);
            border-radius: 20px; box-shadow: var(--shadow); padding: 1.75rem 1.5rem 2rem;
        }
        section { padding: 1.15rem 0; border-top: 1px solid var(--line); }
        section:first-of-type { border-top: 0; padding-top: 0; }
        h2 { font-family: Fraunces, Georgia, serif; font-size: 1.28rem; color: var(--brand-deep); margin: 0 0 0.7rem; }
        p, li { color: var(--muted); line-height: 1.65; font-size: 1rem; }
        p { margin: 0 0 0.85rem; }
        ul { margin: 0 0 0.85rem; padding-left: 1.2rem; }
        li { margin-bottom: 0.35rem; }
        strong { color: var(--ink); }
        a { color: var(--brand); font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
        footer { margin-top: 1.5rem; text-align: center; color: var(--muted); font-size: 0.9rem; }
        @media (min-width: 640px) {
            .page { padding-top: 2.75rem; }
            .hero, .card { padding-left: 2.25rem; padding-right: 2.25rem; }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="hero">
            <div class="brand-row">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $schoolName }} logo">
                @else
                    <div class="brand-mark" aria-hidden="true">RK</div>
                @endif
                <div class="brand-text">
                    <small>Royal Kings Education</small>
                    <strong>{{ $schoolName }}</strong>
                </div>
            </div>
            <h1>Terms of Use</h1>
            <p>
                These terms govern authorized use of {{ $schoolName }} digital systems, including the ERP
                and mobile apps <strong style="color:#fff">Royal Kings Admin</strong> and
                <strong style="color:#fff">Royal Kings Users</strong>.
            </p>
            <div class="meta">
                <span class="chip">Effective {{ $effectiveDate }}</span>
                <span class="chip">Authorized users only</span>
            </div>
        </header>

        <article class="card">
            <section>
                <h2>1. Acceptance</h2>
                <p>
                    By accessing our school management platform or mobile apps, you agree to these Terms
                    of Use and our <a href="{{ url('/privacy') }}">Privacy Policy</a>.
                </p>
            </section>
            <section>
                <h2>2. Authorized use</h2>
                <p>
                    Access is limited to staff, parents/guardians, students, and other users issued
                    credentials by {{ $schoolName }}. Use the correct app for your role:
                    <strong>Royal Kings Admin</strong> for school administration accounts, and
                    <strong>Royal Kings Users</strong> for teachers, parents/guardians, students, drivers,
                    and other non-admin roles. You must keep login details confidential and use the
                    systems only for legitimate school purposes.
                </p>
            </section>
            <section>
                <h2>3. Acceptable behaviour</h2>
                <ul>
                    <li>Do not share accounts or attempt to access data beyond your role</li>
                    <li>Do not disrupt services, reverse-engineer, or misuse school information</li>
                    <li>Parents may only access information about learners linked to their account</li>
                    <li>Report suspected security issues promptly to school administration</li>
                </ul>
            </section>
            <section>
                <h2>4. School records</h2>
                <p>
                    Information in our systems remains the property of {{ $schoolName }} and is provided
                    for school administration. Exporting or sharing records outside authorized channels
                    is prohibited unless approved by the school.
                </p>
            </section>
            <section>
                <h2>5. Availability</h2>
                <p>
                    We aim to keep services available but do not guarantee uninterrupted access.
                    Maintenance, connectivity, or third-party outages may temporarily affect use.
                </p>
            </section>
            <section>
                <h2>6. Contact</h2>
                <p>
                    {{ $schoolName }} · {{ $schoolAddress }} ·
                    <a href="mailto:{{ $schoolEmail }}">{{ $schoolEmail }}</a> ·
                    <a href="tel:{{ preg_replace('/\s+/', '', $schoolPhone) }}">{{ $schoolPhone }}</a>
                </p>
            </section>
        </article>

        <footer>
            <p>&copy; {{ date('Y') }} {{ $schoolName }}. All rights reserved.</p>
            <p><a href="{{ url('/privacy') }}">Privacy Policy</a> · <a href="{{ url('/login') }}">Staff login</a></p>
        </footer>
    </main>
</body>
</html>
