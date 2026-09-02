@php
    $username = $username ?? (auth()->user()?->email ?? auth()->user()?->phone_number ?? '');
    $showCurrent = $showCurrent ?? false;
    $currentRequired = $currentRequired ?? false;
    $generated = $generated ?? \App\Support\PasswordPolicy::generate();
    $passwordName = $passwordName ?? 'new_password';
    $confirmName = $confirmName ?? 'new_password_confirmation';
@endphp
<div class="password-fields" data-generated="{{ $generated }}" data-username="{{ $username }}">
    @if($showCurrent)
    <div class="mb-3">
        <label class="form-label" for="current_password">Current password @unless($currentRequired)<span class="text-muted">(optional if you were given a temporary one)</span>@endunless</label>
        <div class="input-group">
            <input type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" id="current_password" autocomplete="current-password" {{ $currentRequired ? 'required' : '' }}>
            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="current_password" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    @endif

    <input type="text" name="username" value="{{ $username }}" autocomplete="username" class="visually-hidden" tabindex="-1" aria-hidden="true">

    <div class="mb-3">
        <label class="form-label" for="{{ $passwordName }}">New password</label>
        <div class="input-group">
            <input type="password" class="form-control js-new-password @error($passwordName) is-invalid @enderror" name="{{ $passwordName }}" id="{{ $passwordName }}" required autocomplete="new-password" minlength="8">
            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="{{ $passwordName }}" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
        @error($passwordName)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <ul class="list-unstyled small mt-2 mb-0 js-password-checklist">
            <li data-rule="length"><i class="bi bi-circle"></i> At least 8 characters</li>
            <li data-rule="upper"><i class="bi bi-circle"></i> A capital letter (A–Z)</li>
            <li data-rule="lower"><i class="bi bi-circle"></i> A small letter (a–z)</li>
            <li data-rule="digit"><i class="bi bi-circle"></i> A number (0–9)</li>
        </ul>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary js-generate-password">Generate a strong password</button>
            <button type="button" class="btn btn-sm btn-outline-secondary js-save-google">Save in Google Password Manager</button>
        </div>
        <div class="form-text js-password-hint">Use Generate, then Save in Google so you do not have to memorise it.</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="{{ $confirmName }}">Confirm new password</label>
        <div class="input-group">
            <input type="password" class="form-control js-confirm-password" name="{{ $confirmName }}" id="{{ $confirmName }}" required autocomplete="new-password" minlength="8">
            <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="{{ $confirmName }}" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function evaluate(value) {
        return {
            length: value.length >= 8,
            upper: /[A-Z]/.test(value),
            lower: /[a-z]/.test(value),
            digit: /\d/.test(value)
        };
    }
    function generate() {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnopqrstuvwxyz';
        const digits = '23456789';
        const all = upper + lower + digits;
        const chars = [
            upper[Math.floor(Math.random() * upper.length)],
            lower[Math.floor(Math.random() * lower.length)],
            digits[Math.floor(Math.random() * digits.length)]
        ];
        while (chars.length < 10) chars.push(all[Math.floor(Math.random() * all.length)]);
        for (let i = chars.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [chars[i], chars[j]] = [chars[j], chars[i]];
        }
        return chars.join('');
    }
    document.querySelectorAll('.password-fields').forEach(function (root) {
        const newInput = root.querySelector('.js-new-password');
        const confirmInput = root.querySelector('.js-confirm-password');
        const checklist = root.querySelector('.js-password-checklist');
        const username = root.getAttribute('data-username') || '';

        function paint() {
            const result = evaluate(newInput.value || '');
            checklist.querySelectorAll('[data-rule]').forEach(function (li) {
                const ok = result[li.getAttribute('data-rule')];
                const icon = li.querySelector('i');
                li.classList.toggle('text-success', ok);
                li.classList.toggle('text-danger', !ok && (newInput.value || '').length > 0);
                icon.className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            });
        }

        root.querySelectorAll('.js-toggle-password').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const input = document.getElementById(btn.getAttribute('data-target'));
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });

        newInput.addEventListener('input', paint);

        root.querySelector('.js-generate-password').addEventListener('click', function () {
            const value = generate();
            newInput.value = value;
            confirmInput.value = value;
            newInput.type = 'text';
            confirmInput.type = 'text';
            root.querySelectorAll('.js-toggle-password').forEach(function (btn) {
                const icon = btn.querySelector('i');
                if (icon) icon.className = 'bi bi-eye-slash';
            });
            newInput.dispatchEvent(new Event('input'));
        });

        root.querySelector('.js-save-google').addEventListener('click', async function () {
            const password = newInput.value;
            if (!password) {
                alert('Enter or generate a password first.');
                return;
            }
            try {
                if (window.PasswordCredential && navigator.credentials && navigator.credentials.store) {
                    const cred = new PasswordCredential({ id: username || 'school-account', name: username, password: password });
                    await navigator.credentials.store(cred);
                    alert('Saved. Chrome / Google Password Manager should now offer this password.');
                    return;
                }
            } catch (e) {}
            try {
                await navigator.clipboard.writeText(password);
                alert('Copied. In Chrome, open Google Password Manager and save it for this site.');
            } catch (e) {
                alert('Could not save automatically. Copy the password and save it in Google Password Manager.');
            }
        });

        paint();
    });
})();
</script>
@endpush
@endonce
