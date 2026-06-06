<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subject }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #222;">
    <h2 style="color: #b42318;">{{ $subject }}</h2>
    <p>{{ $body }}</p>
    @if(!empty($actionUrl))
        <p><a href="{{ $actionUrl }}" style="display:inline-block;padding:10px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">Open in ERP</a></p>
    @endif
    <p style="font-size:12px;color:#666;">Sent at {{ now()->format('Y-m-d H:i:s') }} · Royal Kings School ERP</p>
</body>
</html>
