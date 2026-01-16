@extends('layouts.app')

@push('styles')
<style>
    .phone-mockup-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        padding: 2rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .phone-frame {
        width: 375px;
        height: 812px;
        background: #000;
        border-radius: 50px;
        padding: 8px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    .phone-screen {
        width: 100%;
        height: 100%;
        background: #f5f5f5;
        border-radius: 42px;
        overflow: hidden;
        position: relative;
    }

    /* Status bar */
    .status-bar {
        height: 44px;
        background: #000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
    }

    .status-left {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .status-right {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 140px;
        height: 30px;
        background: #000;
        border-radius: 0 0 20px 20px;
        z-index: 10;
    }

    /* App header */
    .app-header {
        background: {{ $channel === 'whatsapp' ? '#075e54' : '#ffffff' }};
        color: {{ $channel === 'whatsapp' ? '#fff' : '#000' }};
        padding: 12px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .app-header .back-btn {
        background: none;
        border: none;
        color: {{ $channel === 'whatsapp' ? '#fff' : '#000' }};
        font-size: 20px;
        cursor: pointer;
    }

    .app-header .contact-info {
        flex: 1;
    }

    .app-header .contact-name {
        font-weight: 600;
        font-size: 16px;
    }

    .app-header .contact-phone {
        font-size: 12px;
        opacity: 0.7;
    }

    .app-header .menu-btn {
        background: none;
        border: none;
        color: {{ $channel === 'whatsapp' ? '#fff' : '#000' }};
        font-size: 20px;
        cursor: pointer;
    }

    /* Message area */
    .message-area {
        height: calc(100% - 44px - 60px);
        overflow-y: auto;
        background: {{ $channel === 'whatsapp' ? '#ece5dd' : ($channel === 'email' ? '#ffffff' : '#f0f0f0') }};
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    @if($channel === 'whatsapp')
    .message-bubble {
        max-width: 75%;
        padding: 8px 12px;
        border-radius: 8px;
        word-wrap: break-word;
        position: relative;
    }

    .message-bubble.sent {
        background: #dcf8c6;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }

    .message-bubble.received {
        background: #ffffff;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .message-time {
        font-size: 11px;
        color: #667781;
        margin-top: 4px;
        text-align: right;
    }
    @elseif($channel === 'email')
    .email-content {
        background: #fff;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .email-header {
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 12px;
        margin-bottom: 12px;
    }

    .email-subject {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 8px;
    }

    .email-from {
        font-size: 14px;
        color: #666;
    }

    .email-body {
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        white-space: pre-wrap;
    }
    @else
    .sms-message {
        background: #fff;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 12px;
    }

    .sms-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e0e0e0;
    }

    .sms-from {
        font-weight: 600;
        font-size: 14px;
    }

    .sms-time {
        font-size: 12px;
        color: #666;
    }

    .sms-body {
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        white-space: pre-wrap;
    }
    @endif

    /* Input area */
    .input-area {
        height: 60px;
        background: #fff;
        border-top: 1px solid rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        padding: 8px 16px;
        gap: 8px;
    }

    .input-field {
        flex: 1;
        border: 1px solid #e0e0e0;
        border-radius: 24px;
        padding: 8px 16px;
        font-size: 14px;
    }

    .send-btn {
        background: {{ $channel === 'whatsapp' ? '#25d366' : '#007bff' }};
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .preview-info {
        margin-top: 2rem;
        text-align: center;
        color: #fff;
    }

    .preview-info h3 {
        margin-bottom: 1rem;
    }

    .preview-details {
        background: rgba(255,255,255,0.1);
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        text-align: left;
        display: inline-block;
    }

    .preview-details p {
        margin: 0.5rem 0;
    }

    .back-button {
        position: absolute;
        top: 2rem;
        left: 2rem;
        background: rgba(255,255,255,0.2);
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    .back-button:hover {
        background: rgba(255,255,255,0.3);
    }

    /* Link styling in messages */
    .message-link {
        color: #007bff;
        text-decoration: underline;
        word-break: break-all;
    }
</style>
@endpush

@section('content')
<div class="phone-mockup-container">
    <button onclick="window.history.back()" class="back-button">
        <i class="bi bi-arrow-left"></i> Back
    </button>

    <div class="phone-frame">
        <div class="phone-screen">
            <!-- Notch -->
            <div class="notch"></div>

            <!-- Status Bar -->
            <div class="status-bar">
                <div class="status-left">
                    <span>9:41</span>
                </div>
                <div class="status-right">
                    <span>📶</span>
                    <span>📶</span>
                    <span>🔋</span>
                </div>
            </div>

            <!-- App Header -->
            <div class="app-header">
                <button class="back-btn">‹</button>
                <div class="contact-info">
                    <div class="contact-name">{{ $parentName }}</div>
                    @if($parentContact)
                    <div class="contact-phone">{{ $parentContact }}</div>
                    @endif
                </div>
                <button class="menu-btn">⋮</button>
            </div>

            <!-- Message Area -->
            <div class="message-area">
                @if($channel === 'whatsapp')
                    <div class="message-bubble received">
                        <div class="message-content">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link">$1</a>', nl2br(e($message))) !!}</div>
                        <div class="message-time">{{ now()->format('H:i') }}</div>
                    </div>
                @elseif($channel === 'email')
                    <div class="email-content">
                        <div class="email-header">
                            <div class="email-subject">Message from {{ setting('school_name', 'School') }}</div>
                            <div class="email-from">From: {{ setting('school_email', 'school@example.com') }}</div>
                        </div>
                        <div class="email-body">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link">$1</a>', nl2br(e($message))) !!}</div>
                    </div>
                @else
                    <div class="sms-message">
                        <div class="sms-header">
                            <div class="sms-from">{{ setting('school_name', 'School') }}</div>
                            <div class="sms-time">{{ now()->format('H:i') }}</div>
                        </div>
                        <div class="sms-body">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link">$1</a>', nl2br(e($message))) !!}</div>
                    </div>
                @endif
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <input type="text" class="input-field" placeholder="Type a message..." readonly>
                <button class="send-btn">
                    <i class="bi bi-send"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="preview-info">
        <h3>Message Preview</h3>
        <p>This is how the message will appear to <strong>{{ $parentName }}</strong></p>
        <div class="preview-details">
            <p><strong>Student:</strong> {{ $student->full_name ?? ($student->first_name . ' ' . $student->last_name) }}</p>
            <p><strong>Channel:</strong> {{ strtoupper($channel) }}</p>
            <p><strong>Recipient:</strong> {{ $parentContact ?: 'N/A' }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Make links clickable in preview
    document.querySelectorAll('.message-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Link: ' + this.href);
        });
    });
</script>
@endpush
