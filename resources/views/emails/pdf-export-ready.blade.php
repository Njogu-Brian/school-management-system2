<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $schoolName ?? 'School' }} — PDF export ready</title>
    <style>
        body { margin:0; padding:0; background:#f4f4f4; color:#333; font-family:'Segoe UI', Arial, sans-serif; }
        .email-container { max-width:600px; margin:30px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .email-header { background:#3a1a59; color:#fff; padding:28px 24px; text-align:center; }
        .email-header img { max-height:64px; margin-bottom:10px; }
        .email-header h1 { margin:0; font-size:20px; font-weight:700; }
        .email-header p { margin:6px 0 0; opacity:.9; font-size:13px; }
        .email-body { padding:28px 24px; font-size:15px; line-height:1.65; }
        .email-body h2 { margin:0 0 12px; font-size:18px; color:#3a1a59; }
        .meta { background:#f7f5fb; border:1px solid #e6e0f0; border-radius:8px; padding:14px 16px; margin:18px 0; font-size:14px; }
        .meta strong { color:#222; }
        .btn { display:inline-block; margin-top:8px; padding:12px 22px; background:#3a1a59; color:#fff !important; text-decoration:none; border-radius:6px; font-weight:600; }
        .note { color:#666; font-size:13px; margin-top:18px; }
        .email-footer { background:#f9f9f9; color:#555; text-align:center; font-size:13px; padding:22px; }
        .email-footer a { color:#3a1a59; text-decoration:none; }
    </style>
</head>
<body>
<div class="email-container">
    <div class="email-header">
        @if(!empty($schoolLogo))
            <img src="{{ $schoolLogo }}" alt="{{ $schoolName ?? 'School' }}">
        @endif
        <h1>{{ $schoolName ?? 'School' }}</h1>
        @if(!empty($schoolMotto))
            <p>{{ $schoolMotto }}</p>
        @endif
    </div>
    <div class="email-body">
        <h2>{{ !empty($isZip) ? 'Your PDF export ZIP is ready' : 'Your PDF export is ready' }}</h2>
        <p>Hi {{ $userName ?? 'there' }},</p>
        <p>Your requested export has finished generating and is ready to download.</p>
        <div class="meta">
            <div><strong>Export:</strong> {{ $title ?? 'PDF export' }}</div>
            <div><strong>File:</strong> {{ $filename ?? 'document.pdf' }}</div>
            @if(!empty($itemCount))
                <div><strong>Items:</strong> {{ number_format((int) $itemCount) }}</div>
            @endif
        </div>
        <p style="text-align:center;">
            <a class="btn" href="{{ $downloadUrl }}">{{ !empty($isZip) ? 'Download ZIP' : 'Download PDF' }}</a>
        </p>
        <p class="note">
            This download link does not expire. Sign in to the school portal if prompted, then the file will download from secure storage.
        </p>
        <p class="note" style="word-break:break-all;">
            Direct link:<br>{{ $downloadUrl }}
        </p>
    </div>
    <div class="email-footer">
        <p>{{ $schoolName ?? 'School' }}</p>
        <p>&copy; {{ date('Y') }} {{ $schoolName ?? 'School' }}. All rights reserved.</p>
    </div>
</div>
</body>
</html>
