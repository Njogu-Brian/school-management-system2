@php
    use App\Support\FamilyUpdateFeedback;
    $hasSuccess = session()->has('success');
    $hasError = session()->has('error');
    $hasWarning = session()->has('warning');
    $hasFieldErrors = $errors->any();
    $isPartialSave = $hasSuccess && $hasFieldErrors;
    $showFeedback = $hasSuccess || $hasError || $hasWarning || $hasFieldErrors;
@endphp

@if($showFeedback)
    <div class="feedback-anchor" id="formFeedback" tabindex="-1" aria-live="polite">
        @if($hasSuccess)
            <div class="alert alert-success feedback-alert" role="status">
                <div class="feedback-alert__icon" aria-hidden="true">✓</div>
                <div class="feedback-alert__body">
                    <strong>Saved successfully</strong>
                    <p class="mb-0">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if($hasError)
            <div class="alert alert-danger feedback-alert" role="alert">
                <div class="feedback-alert__icon" aria-hidden="true">!</div>
                <div class="feedback-alert__body">
                    <strong>We could not save your form</strong>
                    <p class="mb-0">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($hasWarning && ! $isPartialSave)
            <div class="alert alert-warning feedback-alert" role="status">
                <div class="feedback-alert__icon" aria-hidden="true">i</div>
                <div class="feedback-alert__body">
                    <p class="mb-0">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        @if($isPartialSave)
            <div class="alert alert-warning feedback-alert" role="status">
                <div class="feedback-alert__icon" aria-hidden="true">i</div>
                <div class="feedback-alert__body">
                    <strong>Almost there!</strong>
                    <p class="mb-2">{{ session('warning', 'Some details still need your attention. Your other changes were saved.') }}</p>
                    <p class="mb-0 small text-muted">Scroll down to the highlighted fields, complete them, and tap <strong>Save my details</strong> again.</p>
                </div>
            </div>
        @endif

        @if($hasFieldErrors)
            <div class="alert {{ $isPartialSave ? 'alert-warning' : 'alert-danger' }} feedback-alert feedback-alert--list" role="alert">
                <div class="feedback-alert__icon" aria-hidden="true">{{ $isPartialSave ? 'i' : '!' }}</div>
                <div class="feedback-alert__body">
                    <strong>{{ $isPartialSave ? 'Please complete these items' : 'Please check the following' }}</strong>
                    <ul class="feedback-error-list mb-0 mt-2">
                        @foreach($errors->messages() as $field => $messages)
                            @foreach($messages as $message)
                                <li data-error-field="{{ $field }}">
                                    <span class="feedback-error-label">{{ FamilyUpdateFeedback::fieldLabel($field) }}:</span>
                                    {{ FamilyUpdateFeedback::friendlyMessage($message) }}
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
