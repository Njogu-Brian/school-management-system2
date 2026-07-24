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
    <meta name="description" content="Privacy Policy for {{ $schoolName }} school management systems and mobile apps, including Royal Kings Admin and Royal Kings Users.">
    <title>Privacy Policy — {{ $schoolName }}</title>
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

        .page {
            max-width: 820px;
            margin: 0 auto;
            padding: 2rem 1.25rem 3.5rem;
        }

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

        .brand-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }

        .brand-row img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 14px;
            padding: 6px;
        }

        .brand-mark {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.15);
            font-family: Fraunces, Georgia, serif;
            font-weight: 700;
            font-size: 1.35rem;
            letter-spacing: 0.02em;
        }

        .brand-text small {
            display: block;
            opacity: 0.85;
            font-size: 0.85rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.2rem;
        }

        .brand-text strong {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .hero h1 {
            position: relative;
            z-index: 1;
            font-family: Fraunces, Georgia, serif;
            font-size: clamp(1.85rem, 4vw, 2.45rem);
            line-height: 1.15;
            margin: 1.35rem 0 0.65rem;
            font-weight: 700;
        }

        .hero p {
            position: relative;
            z-index: 1;
            margin: 0;
            max-width: 38rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.02rem;
            line-height: 1.55;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1.25rem;
            position: relative;
            z-index: 1;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            font-size: 0.85rem;
        }

        .card {
            margin-top: 1.25rem;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 1.75rem 1.5rem 2rem;
        }

        .toc {
            display: grid;
            gap: 0.45rem;
            margin: 0 0 1.75rem;
            padding: 1rem 1.1rem;
            background: var(--sky);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .toc a {
            color: var(--brand);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .toc a:hover { text-decoration: underline; }

        section {
            padding: 1.15rem 0;
            border-top: 1px solid var(--line);
        }

        section:first-of-type { border-top: 0; padding-top: 0; }

        h2 {
            font-family: Fraunces, Georgia, serif;
            font-size: 1.28rem;
            color: var(--brand-deep);
            margin: 0 0 0.7rem;
        }

        p, li {
            color: var(--muted);
            line-height: 1.65;
            font-size: 1rem;
        }

        p { margin: 0 0 0.85rem; }

        ul {
            margin: 0 0 0.85rem;
            padding-left: 1.2rem;
        }

        li { margin-bottom: 0.35rem; }

        strong { color: var(--ink); }

        .callout {
            margin: 1rem 0;
            padding: 1rem 1.1rem;
            border-left: 4px solid var(--accent);
            background: #fffaf0;
            border-radius: 0 12px 12px 0;
            color: var(--ink);
        }

        .contact-grid {
            display: grid;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .contact-item {
            padding: 0.85rem 1rem;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--sky);
        }

        .contact-item span {
            display: block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--muted);
            margin-bottom: 0.2rem;
        }

        .contact-item a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
        }

        .contact-item a:hover { text-decoration: underline; }

        footer {
            margin-top: 1.5rem;
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
        }

        footer a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
        }

        @media (min-width: 640px) {
            .page { padding-top: 2.75rem; }
            .hero, .card { padding-left: 2.25rem; padding-right: 2.25rem; }
            .contact-grid { grid-template-columns: 1fr 1fr; }
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
            <h1>Privacy Policy</h1>
            <p>
                This policy explains how {{ $schoolName }} collects, uses, and protects information
                in our school management platform and mobile apps — including
                <strong style="color:#fff">Royal Kings Admin</strong> and
                <strong style="color:#fff">Royal Kings Users</strong>.
            </p>
            <div class="meta">
                <span class="chip">Effective {{ $effectiveDate }}</span>
                <span class="chip">Public document</span>
                <span class="chip">Staff, parents &amp; school community</span>
            </div>
        </header>

        <article class="card">
            <nav class="toc" aria-label="Contents">
                <a href="#who-we-are">1. Who we are</a>
                <a href="#scope">2. Scope of this policy</a>
                <a href="#data-we-collect">3. Information we collect</a>
                <a href="#how-we-use">4. How we use information</a>
                <a href="#sharing">5. Sharing &amp; disclosure</a>
                <a href="#retention">6. Retention &amp; security</a>
                <a href="#rights">7. Your rights</a>
                <a href="#children">8. Children &amp; student data</a>
                <a href="#apps">9. Mobile apps</a>
                <a href="#changes">10. Changes to this policy</a>
                <a href="#contact">11. Contact us</a>
            </nav>

            <section id="who-we-are">
                <h2>1. Who we are</h2>
                <p>
                    <strong>{{ $schoolName }}</strong> (“we”, “us”, “our”) operates school administration,
                    academic, finance, and communication systems for our school community in Kenya.
                    Our digital services include the web ERP at
                    <strong>erp.royalkingsschools.sc.ke</strong> and related mobile applications:
                    <strong>Royal Kings Admin</strong> (school administration) and
                    <strong>Royal Kings Users</strong> (teachers, parents/guardians, students, drivers, and other authorized non-admin users).
                </p>
                <div class="callout">
                    We are committed to safeguarding personal data entrusted to us by staff, parents,
                    guardians, and learners, in line with Kenya’s Data Protection Act, 2019 and our
                    duty of care as a Christian, child-centered school.
                </div>
            </section>

            <section id="scope">
                <h2>2. Scope of this policy</h2>
                <p>This Privacy Policy applies to:</p>
                <ul>
                    <li>Our school management / ERP website and APIs</li>
                    <li>Official mobile apps published by {{ $schoolName }}, including <strong>Royal Kings Admin</strong> and <strong>Royal Kings Users</strong></li>
                    <li>Related school portals used by authorized staff, parents/guardians, and (where issued) student accounts</li>
                </ul>
                <p>
                    It does not cover third-party websites linked from our systems (for example payment
                    gateways or messaging providers), which have their own privacy practices.
                </p>
            </section>

            <section id="data-we-collect">
                <h2>3. Information we collect</h2>
                <p>Depending on your role and how you use our systems, we may process:</p>
                <ul>
                    <li><strong>Account &amp; identity data</strong> — name, email, phone number, staff or parent role, login credentials</li>
                    <li><strong>Authentication data</strong> — session tokens, optional one-time passwords (OTP) for parent account claim, optional app PIN, and optional biometric unlock preferences stored on your device</li>
                    <li><strong>School records</strong> — student enrolment, attendance, academics, homework, fees (where your role permits), transport, pastoral notes, concerns/complaints, and school communications</li>
                    <li><strong>Location data</strong> — approximate or precise location when staff use location-based features such as clock-in / attendance verification in Royal Kings Users (only while that feature is used and with device permission)</li>
                    <li><strong>Photos &amp; media</strong> — profile photos and files you attach (for example homework or concern supporting images) when you choose to upload them</li>
                    <li><strong>Device &amp; technical data</strong> — app version, device type, approximate diagnostics needed to keep services secure and reliable</li>
                    <li><strong>Support communications</strong> — messages you send to school IT or administration</li>
                </ul>
                <p>
                    Biometric data (fingerprint / face unlock), where enabled, is processed by your device’s
                    operating system. We do not receive or store raw biometric templates on our servers.
                    App PIN codes are stored securely on your device for local unlock and are not sent to our servers as passwords.
                </p>
            </section>

            <section id="how-we-use">
                <h2>4. How we use information</h2>
                <p>We use personal information to:</p>
                <ul>
                    <li>Provide secure access to school systems for authorized users</li>
                    <li>Manage academics, attendance, homework, fees, transport, HR, pastoral care, and school communications</li>
                    <li>Enable parents/guardians to view permitted information about their linked children and raise concerns with the school</li>
                    <li>Verify staff presence or clock-in where location is required for school operations</li>
                    <li>Protect accounts against unauthorized access and abuse</li>
                    <li>Improve reliability, support, and service quality</li>
                    <li>Meet legal, regulatory, and safeguarding obligations</li>
                </ul>
                <p>
                    We do <strong>not</strong> sell personal data. We do not use student or parent data for
                    unrelated advertising.
                </p>
            </section>

            <section id="sharing">
                <h2>5. Sharing &amp; disclosure</h2>
                <p>We may share information only when necessary, for example with:</p>
                <ul>
                    <li>Authorized school staff who need it to perform their duties</li>
                    <li>Trusted service providers that host or support our systems (under appropriate safeguards)</li>
                    <li>Payment or messaging providers when you use those features</li>
                    <li>Authorities where required by law or to protect the safety of learners</li>
                </ul>
            </section>

            <section id="retention">
                <h2>6. Retention &amp; security</h2>
                <p>
                    We retain information for as long as needed for school operations, legal compliance,
                    and legitimate educational records. When data is no longer required, we delete or
                    anonymize it where practicable.
                </p>
                <p>
                    We apply administrative, technical, and organizational measures appropriate to the
                    sensitivity of school data, including access controls and encrypted transport (HTTPS).
                    No method of transmission or storage is perfectly secure; we continuously work to
                    reduce risk.
                </p>
            </section>

            <section id="rights">
                <h2>7. Your rights</h2>
                <p>Subject to applicable Kenyan law, you may request to:</p>
                <ul>
                    <li>Access personal data we hold about you</li>
                    <li>Request correction of inaccurate information</li>
                    <li>Request deletion or restriction where legally available</li>
                    <li>Object to certain processing, where applicable</li>
                </ul>
                <p>
                    Staff and parent account holders should contact the school using the details below.
                    Some requests may be limited where we must keep records for legal, safeguarding, or
                    educational purposes.
                </p>
            </section>

            <section id="children">
                <h2>8. Children &amp; student data</h2>
                <p>
                    Our systems process learner information for school administration.
                    <strong>Royal Kings Admin</strong> is intended for authorized adult staff.
                    <strong>Royal Kings Users</strong> is intended for teachers, parents/guardians, drivers,
                    and other authorized adults; student accounts are issued only where the school has
                    authorized a learner to use the app. Student data is handled under the school’s
                    legitimate educational purpose and parental / guardian relationship with the school.
                </p>
            </section>

            <section id="apps">
                <h2>9. Mobile apps (Royal Kings Admin &amp; Royal Kings Users)</h2>
                <p>When you use our mobile applications:</p>
                <ul>
                    <li><strong>Royal Kings Admin</strong> — school administration features for authorized staff (operations, finance, academics administration, and related tools)</li>
                    <li><strong>Royal Kings Users</strong> — day-to-day school features for teachers, parents/guardians, students (where issued), and drivers (attendance, academics views, homework, concerns, transport, payslips where permitted, and similar)</li>
                    <li>Sign-in requires school-issued credentials (and optional OTP for parent claim, optional app PIN, or on-device biometrics)</li>
                    <li>The apps communicate with our ERP APIs to display only information your role is permitted to see</li>
                    <li>Location permission, where requested, is used for staff operational features such as clock-in and can be denied in device settings (some features may then be unavailable)</li>
                    <li>Photo / media permission is used only when you choose to set a profile photo or attach images/files</li>
                    <li>Optional biometric unlock and app PIN stay on your device and can be disabled in settings</li>
                    <li>You may uninstall an app at any time; server-side school records remain governed by this policy</li>
                </ul>
            </section>

            <section id="changes">
                <h2>10. Changes to this policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. The “Effective” date at the top
                    of this page will be revised when material changes are made. Continued use of our
                    systems after an update constitutes notice of the revised policy.
                </p>
            </section>

            <section id="contact">
                <h2>11. Contact us</h2>
                <p>
                    For privacy questions, data access requests, or concerns about how we handle information:
                </p>
                <div class="contact-grid">
                    <div class="contact-item">
                        <span>School</span>
                        <strong>{{ $schoolName }}</strong>
                    </div>
                    <div class="contact-item">
                        <span>Address</span>
                        <strong>{{ $schoolAddress }}</strong>
                    </div>
                    <div class="contact-item">
                        <span>Email</span>
                        <a href="mailto:{{ $schoolEmail }}">{{ $schoolEmail }}</a>
                    </div>
                    <div class="contact-item">
                        <span>Phone</span>
                        <a href="tel:{{ preg_replace('/\s+/', '', $schoolPhone) }}">{{ $schoolPhone }}</a>
                    </div>
                </div>
            </section>
        </article>

        <footer>
            <p>&copy; {{ date('Y') }} {{ $schoolName }}. All rights reserved.</p>
            <p><a href="{{ url('/login') }}">Staff login</a> · <a href="https://royalkingsschools.sc.ke">School website</a></p>
        </footer>
    </main>
</body>
</html>
