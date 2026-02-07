@extends('layouts.app')

@push('styles')
<style>
    .preview-page {
        padding: 2rem;
        background: #f5f5f5;
        min-height: calc(100vh - 60px);
    }

    .preview-container {
        max-width: 1400px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 2rem;
        align-items: start;
    }

    .phone-section {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2rem;
    }

    /* Galaxy S25 Ultra - Realistic dimensions */
    .phone-frame {
        width: 390px;
        height: 844px;
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border-radius: 55px;
        padding: 12px;
        box-shadow: 
            0 0 0 2px rgba(255,255,255,0.1),
            0 30px 80px rgba(0, 0, 0, 0.6),
            inset 0 0 50px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .phone-screen {
        width: 100%;
        height: 100%;
        background: #000;
        border-radius: 43px;
        overflow: hidden;
        position: relative;
    }

    /* Dynamic Island / Notch - S25 Ultra style */
    .dynamic-island {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 126px;
        height: 37px;
        background: #000;
        border-radius: 19px;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    /* Status bar */
    .status-bar {
        height: 54px;
        background: transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 24px;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        padding-top: 8px;
    }

    .status-left {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }

    .status-right {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 16px;
    }

    /* App header styles */
    .app-header {
        padding: 8px 16px 8px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    @if($channel === 'whatsapp')
    .app-header {
        background: #075e54;
        color: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    @elseif($channel === 'email')
    .app-header {
        background: #fff;
        color: #202124;
        padding: 12px 16px;
        gap: 16px;
        border-bottom: 1px solid #e8eaed;
    }
    @else
    .app-header {
        background: #0084ff;
        color: #fff;
        padding: 12px 16px;
    }
    @endif

    .app-header .back-btn {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    @if($channel === 'whatsapp')
    .app-header .back-btn {
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .app-header .back-btn:hover {
        background: rgba(255,255,255,0.1);
    }

    .app-header .contact-info {
        flex: 1;
        min-width: 0;
    }

    .app-header .contact-name {
        font-weight: 500;
        font-size: 16px;
        line-height: 1.2;
        color: #fff;
    }

    .app-header .contact-phone {
        font-size: 13px;
        opacity: 0.8;
        margin-top: 2px;
        color: #fff;
    }

    .app-header .menu-btn {
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .app-header .menu-btn:hover {
        background: rgba(255,255,255,0.1);
    }

    /* WhatsApp message area */
    .message-area {
        height: calc(100% - 54px - 70px);
        overflow-y: auto;
        background: #efeae2;
        background-image: 
            repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(0,0,0,.03) 2px, rgba(0,0,0,.03) 4px);
        padding: 10px 16px 16px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    @elseif($channel === 'email')
    .app-header .back-btn {
        color: #5f6368;
        padding: 8px;
    }

    .app-header .contact-info {
        flex: 1;
    }

    .app-header .contact-name {
        font-weight: 500;
        font-size: 16px;
        color: #202124;
    }

    .app-header .contact-phone {
        font-size: 14px;
        color: #5f6368;
        margin-top: 2px;
    }

    .app-header .menu-btn {
        background: none;
        border: none;
        color: #5f6368;
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
    }

    .message-area {
        height: calc(100% - 54px - 70px);
        overflow-y: auto;
        background: #fff;
        padding: 16px;
    }
    @else
    .app-header .back-btn {
        color: #fff;
        padding: 4px;
    }

    .app-header .contact-info {
        flex: 1;
    }

    .app-header .contact-name {
        font-weight: 500;
        font-size: 18px;
        color: #fff;
    }

    .app-header .contact-phone {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 2px;
        color: #fff;
    }

    .app-header .menu-btn {
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        padding: 4px;
    }

    .message-area {
        height: calc(100% - 54px - 70px);
        overflow-y: auto;
        background: #e5ddd5;
        padding: 16px;
    }
    @endif

    .message-bubble {
        max-width: 75%;
        padding: 6px 7px 8px 9px;
        border-radius: 7.5px;
        word-wrap: break-word;
        position: relative;
        font-size: 14.2px;
        line-height: 19px;
        color: #303030;
    }

    .message-bubble.sent {
        background: #dcf8c6;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
        box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
    }

    .message-bubble.received {
        background: #ffffff;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
        box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
    }

    .message-time {
        font-size: 11px;
        color: #667781;
        margin-top: 2px;
        text-align: right;
        line-height: 15px;
    }

    .message-content {
        word-wrap: break-word;
    }
    @if($channel === 'email')
    .email-content {
        background: #fff;
        border-radius: 8px;
    }

    .email-header {
        border-bottom: 1px solid #e8eaed;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }

    .email-subject {
        font-weight: 500;
        font-size: 20px;
        color: #202124;
        margin-bottom: 8px;
    }

    .email-from {
        font-size: 14px;
        color: #5f6368;
    }

    .email-body {
        font-size: 14px;
        line-height: 1.6;
        color: #202124;
        white-space: pre-wrap;
    }
    @elseif($channel === 'sms')
    .sms-message {
        background: #fff;
        border-radius: 18px;
        padding: 12px 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        margin-bottom: 8px;
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
        font-size: 15px;
        color: #333;
    }

    .sms-time {
        font-size: 12px;
        color: #666;
    }

    .sms-body {
        font-size: 15px;
        line-height: 1.5;
        color: #333;
        white-space: pre-wrap;
    }
    @endif

    /* Input area */
    .input-area {
        height: 70px;
        background: #fff;
        border-top: 1px solid rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        padding: 8px 12px;
        gap: 8px;
    }

    .input-field {
        flex: 1;
        border: 1px solid #e0e0e0;
        border-radius: 21px;
        padding: 9px 16px;
        font-size: 15px;
        background: #f0f0f0;
    }

    .send-btn {
        background: {{ $channel === 'whatsapp' ? '#25d366' : ($channel === 'email' ? '#1a73e8' : '#0084ff') }};
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Action buttons */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        min-width: 200px;
    }

    .action-btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-back {
        background: #6c757d;
        color: #fff;
    }

    .btn-send {
        background: {{ $channel === 'whatsapp' ? '#25d366' : ($channel === 'email' ? '#1a73e8' : '#007bff') }};
        color: #fff;
    }

    .preview-info {
        background: #fff;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .preview-info h4 {
        margin-bottom: 1rem;
        color: #333;
    }

    .preview-details {
        margin-top: 1rem;
    }

    .preview-details p {
        margin: 0.5rem 0;
        color: #666;
        font-size: 14px;
    }

    .preview-details strong {
        color: #333;
    }

    /* Link styling */
    .message-link {
        color: #0084ff;
        text-decoration: none;
        word-break: break-all;
    }

    .message-link:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('content')
<div class="preview-page">
    <div class="preview-container">
        <div class="phone-section">
            <div class="phone-frame">
                <div class="phone-screen">
                    <!-- Dynamic Island -->
                    <div class="dynamic-island"></div>

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
                            <div class="message-bubble sent">
                                <div class="message-content">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link" target="_blank">$1</a>', nl2br(e($message))) !!}</div>
                                <div class="message-time">{{ now()->format('H:i') }}</div>
                            </div>
                        @elseif($channel === 'email')
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-subject">Message from {{ setting('school_name', 'School') }}</div>
                                    <div class="email-from">From: {{ setting('school_email', 'school@example.com') }}</div>
                                </div>
                                <div class="email-body">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link" target="_blank">$1</a>', nl2br(e($message))) !!}</div>
                            </div>
                        @else
                            <div class="sms-message">
                                <div class="sms-header">
                                    <div class="sms-from">{{ setting('school_name', 'School') }}</div>
                                    <div class="sms-time">{{ now()->format('H:i') }}</div>
                                </div>
                                <div class="sms-body">{!! preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" class="message-link" target="_blank">$1</a>', nl2br(e($message))) !!}</div>
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
        </div>

        <div class="action-buttons">
            <div class="preview-info">
                <h4>Message Preview</h4>
                <p>This is how the message will appear to <strong>{{ $parentName }}</strong></p>
                <div class="preview-details">
                    <p><strong>Student:</strong> {{ isset($student) ? ($student->full_name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) : 'N/A') }}</p>
                    <p><strong>Channel:</strong> {{ strtoupper($channel) }}</p>
                    <p><strong>Recipient:</strong> {{ $parentContact ?: 'N/A' }}</p>
                </div>
            </div>
            
            <button onclick="window.history.back()" class="action-btn btn-back">
                <i class="bi bi-arrow-left"></i> Back to Edit
            </button>
            
            <form method="POST" action="{{ route('communication.send.' . $channel . '.submit') }}" id="sendForm" style="display: none;">
                @csrf
                <input type="hidden" name="message" value="{{ htmlspecialchars($originalMessage ?? $formData['message'] ?? '') }}">
                <input type="hidden" name="target" value="{{ $formData['target'] ?? '' }}">
                <input type="hidden" name="classroom_id" value="{{ $formData['classroom_id'] ?? '' }}">
                <input type="hidden" name="student_id" value="{{ $formData['student_id'] ?? '' }}">
                <input type="hidden" name="selected_student_ids" value="{{ $formData['selected_student_ids'] ?? '' }}">
                <input type="hidden" name="template_id" value="{{ $formData['template_id'] ?? '' }}">
                <input type="hidden" name="schedule" value="now">
                @if($channel === 'sms')
                <input type="hidden" name="sender_id" value="">
                @endif
            </form>
            
            <button onclick="document.getElementById('sendForm').submit()" class="action-btn btn-send">
                <i class="bi bi-{{ $channel === 'whatsapp' ? 'whatsapp' : ($channel === 'email' ? 'envelope' : 'send') }}"></i> Send {{ strtoupper($channel) }}
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Make links clickable in preview
    document.querySelectorAll('.message-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.stopPropagation();
            // Allow default link behavior
        });
    });
</script>
@endpush
