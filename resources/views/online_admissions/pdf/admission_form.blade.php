<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admission Form</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; line-height: 1.4; }
        h1 { font-size: 18px; margin: 0 0 4px; text-align: center; }
        h2 { font-size: 13px; margin: 18px 0 8px; border-bottom: 1px solid #333; padding-bottom: 3px; }
        .meta { text-align: center; color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        td { padding: 4px 6px; vertical-align: top; }
        .label { width: 32%; color: #555; }
        .value { width: 68%; border-bottom: 1px dotted #999; min-height: 16px; }
        .policy { white-space: pre-wrap; margin: 6px 0 12px; }
        .sign-box { margin-top: 24px; }
        .sign-line { margin-top: 28px; border-top: 1px solid #333; width: 45%; display: inline-block; padding-top: 4px; }
        .sign-row { width: 100%; }
        .muted { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>{{ $schoolName }}</h1>
    <div class="meta">
        Admission Form
        @if($schoolPhone || $schoolEmail)
            <br>{{ collect([$schoolPhone, $schoolEmail])->filter()->implode(' · ') }}
        @endif
        @if(!empty($admission->application_no))
            <br>Application No: {{ $admission->application_no }}
        @endif
    </div>

    <p class="policy">{{ $policies['intro'] }}</p>

    <h2>Learner details</h2>
    <table>
        <tr><td class="label">Full name</td><td class="value">{{ $childName }}</td></tr>
        <tr><td class="label">Date of birth</td><td class="value">{{ optional($admission->dob)->format('d M Y') }}</td></tr>
        <tr><td class="label">Gender</td><td class="value">{{ $admission->gender }}</td></tr>
        <tr><td class="label">Class applied</td><td class="value">{{ $className }}</td></tr>
        <tr><td class="label">Previous school</td><td class="value">{{ $admission->previous_school }}</td></tr>
        <tr><td class="label">Residential area</td><td class="value">{{ $admission->residential_area }}</td></tr>
        <tr><td class="label">NEMIS / KEMIS</td><td class="value">{{ $admission->nemis_number }}</td></tr>
    </table>

    <h2>Parent / Guardian</h2>
    <table>
        <tr><td class="label">Father</td><td class="value">{{ $admission->father_name }} · {{ $admission->father_phone }} · {{ $admission->father_email }}</td></tr>
        <tr><td class="label">Mother</td><td class="value">{{ $admission->mother_name }} · {{ $admission->mother_phone }} · {{ $admission->mother_email }}</td></tr>
        <tr><td class="label">Guardian</td><td class="value">{{ $admission->guardian_name }} · {{ $admission->guardian_phone }}</td></tr>
        <tr><td class="label">ID numbers</td><td class="value">F: {{ $admission->father_id_number }} · M: {{ $admission->mother_id_number }}</td></tr>
        <tr><td class="label">Emergency contact</td><td class="value">{{ $admission->emergency_contact_name }} · {{ $admission->emergency_contact_phone }}</td></tr>
    </table>

    <h2>Medical</h2>
    <table>
        <tr><td class="label">Allergies</td><td class="value">{{ $admission->has_allergies ? ('Yes — '.$admission->allergies_notes) : 'No' }}</td></tr>
        <tr><td class="label">Fully immunized</td><td class="value">{{ $admission->is_fully_immunized ? 'Yes' : 'No / Not stated' }}</td></tr>
        <tr><td class="label">Preferred hospital</td><td class="value">{{ $admission->preferred_hospital }}</td></tr>
        <tr><td class="label">Medical notes</td><td class="value">{{ $admission->medical_condition }}</td></tr>
    </table>

    <h2>Fees payment policy</h2>
    <div class="policy">{{ $policies['fees_policy'] }}</div>

    <h2>Dress code</h2>
    <div class="policy">{{ $policies['dress_code'] }}</div>

    <h2>Other school policies</h2>
    <div class="policy">{{ $policies['other_policies'] }}</div>

    <div class="sign-box">
        <p>I confirm that the information above is true and that I have read and agree to the school policies.</p>
        <div class="sign-row">
            <div class="sign-line">Parent / Guardian signature</div>
            &nbsp;&nbsp;&nbsp;
            <div class="sign-line">Date</div>
        </div>
        <p class="muted" style="margin-top:20px;">Office use: interview outcome {{ $admission->interview_outcome ?: '—' }} · fee paid {{ $admission->interview_fee_paid ? 'Yes' : 'No' }}</p>
    </div>
</body>
</html>
