@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell" style="max-width: 640px;">
        <div class="page-header">
            <div class="crumb">Account</div>
            <h1>{{ $forced ? 'Create a new password' : 'Change password' }}</h1>
            <p>
                @if($forced)
                    Your school requires a new password before you continue. Use at least 8 characters with a capital letter, a small letter, and a number.
                @else
                    Choose a password that is at least 8 characters, with a capital letter, a small letter, and a number.
                @endif
            </p>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-body">
                <form method="POST" action="{{ route('password.change.update') }}" autocomplete="on">
                    @csrf
                    @include('partials.password-fields', [
                        'showCurrent' => true,
                        'currentRequired' => ! $forced,
                        'generated' => $generated,
                        'username' => auth()->user()->email ?: auth()->user()->phone_number,
                    ])
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        @unless($forced)
                            <a href="{{ url()->previous() }}" class="btn btn-ghost-strong">Cancel</a>
                        @endunless
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check2-circle"></i> Save new password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
