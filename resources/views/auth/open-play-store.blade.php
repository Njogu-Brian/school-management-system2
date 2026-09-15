<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url={{ $httpsUrl }}">
    <title>Open Google Play</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 48px 20px; color: #1f2937; }
        a { color: #004A99; font-weight: 700; }
    </style>
</head>
<body>
    <p>Opening Google Play…</p>
    <p><a id="play-link" href="{{ $httpsUrl }}">Tap here if Play Store does not open</a></p>
    <script>
        (function () {
            var pkg = @json($package);
            var httpsUrl = @json($httpsUrl);
            var isAndroid = /Android/i.test(navigator.userAgent || '');
            var intent = 'intent://details?id=' + encodeURIComponent(pkg)
                + '#Intent;scheme=market;action=android.intent.action.VIEW;package=com.android.vending;S.browser_fallback_url='
                + encodeURIComponent(httpsUrl) + ';end';
            var market = 'market://details?id=' + encodeURIComponent(pkg);
            if (isAndroid) {
                window.location.href = intent;
                setTimeout(function () { window.location.href = market; }, 400);
                setTimeout(function () { window.location.href = httpsUrl; }, 1400);
            } else {
                window.location.href = httpsUrl;
            }
        })();
    </script>
</body>
</html>
