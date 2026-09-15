@php
    $username = $username ?? session('parent_app_username');
    $prominent = $prominent ?? false;
@endphp
<div class="app-download {{ $prominent ? 'app-download-prominent' : '' }}">
    @if($prominent)
        <h6 class="mb-2">Use the Royal Kings Users app</h6>
        <p class="text-muted small mb-3">
            Parent accounts sign in on the mobile app only — not this staff website.
        </p>
        @if(filled($username))
            <div class="app-username mb-3">
                <div class="small text-muted">Your username</div>
                <div class="fw-semibold">{{ $username }}</div>
            </div>
        @endif
    @else
        <div class="fw-semibold mb-2">Parents: use the mobile app</div>
    @endif

    <a href="{{ route('app.play-store') }}" class="btn btn-primary w-100 mb-2" rel="noopener">
        <i class="bi bi-phone me-1"></i> Get it on Google Play
    </a>
    <a href="{{ route('app.apk') }}" class="btn btn-outline-secondary w-100 mb-3">
        <i class="bi bi-download me-1"></i> Download Android APK
    </a>
    <div class="ios-note">
        <i class="bi bi-apple me-1"></i>
        <strong>iPhone / iPad:</strong> the iOS app is under development and will be ready soon.
        For now, use an Android phone from Google Play.
    </div>
</div>
