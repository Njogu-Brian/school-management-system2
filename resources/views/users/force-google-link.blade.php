@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb">Users / Security</div>
            <h1>Google sign-in prompt</h1>
            <p>
                After someone signs in with password or OTP, the mobile app can ask them to link Google
                (with a Skip button). Linked accounts can use “Continue with Google” next time.
            </p>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Who gets asked?</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.require-google-link.mode') }}" class="d-flex flex-column gap-3">
                    @csrf
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="google_link_prompt_mode" id="gl_mode_all" value="all" @checked($mode === 'all')>
                        <label class="form-check-label" for="gl_mode_all">
                            <strong>Everyone</strong> — prompt all users who have not linked Google yet (recommended for rollout).
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="google_link_prompt_mode" id="gl_mode_selected" value="selected" @checked($mode === 'selected')>
                        <label class="form-check-label" for="gl_mode_selected">
                            <strong>Selected users only</strong> — only people marked below.
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="google_link_prompt_mode" id="gl_mode_off" value="off" @checked($mode === 'off')>
                        <label class="form-check-label" for="gl_mode_off">
                            <strong>Off</strong> — no post-login prompt (login screen “Continue with Google” still works if configured).
                        </label>
                    </div>
                    <div>
                        <button class="btn btn-settings-primary" type="submit"><i class="bi bi-save"></i> Save prompt mode</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Group</label>
                        <select name="group" class="form-select" onchange="this.form.submit()">
                            <option value="staff" @selected($group === 'staff')>Staff</option>
                            <option value="parents" @selected($group === 'parents')>Parents</option>
                            <option value="all" @selected($group === 'all')>Staff and parents</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="Name, email or phone">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-search"></i> Find</button>
                    </div>
                </form>
            </div>
        </div>

        <form method="POST" action="{{ route('users.require-google-link.store') }}">
            @csrf
            <input type="hidden" name="group" value="{{ $group }}">
            <input type="hidden" name="q" value="{{ $search }}">

            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ ucfirst($group === 'all' ? 'Staff and parents' : $group) }} <span class="input-chip">{{ $users->total() }}</span></h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary" type="submit" name="apply_to" value="selected"
                                onclick="return confirm('Mark selected users to be asked to link Google after next password/OTP login?')">
                            Require for selected
                        </button>
                        <button class="btn btn-settings-primary" type="submit" name="apply_to" value="all_matching"
                                onclick="return confirm('Mark everyone in this {{ $group }} list?')">
                            Require for everyone in this list
                        </button>
                        <button class="btn btn-outline-secondary" type="submit" name="apply_to" value="clear_selected"
                                onclick="return confirm('Clear Google link requirement for selected users?')">
                            Clear selected
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" class="form-check-input" id="selectAllUsers"></th>
                                    <th>Name</th>
                                    <th>Login</th>
                                    <th>Group</th>
                                    <th>Google</th>
                                    <th>Prompt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input js-user-id" name="user_ids[]" value="{{ $user->id }}"></td>
                                        <td class="fw-semibold">{{ $user->name }}</td>
                                        <td>{{ $user->email ?: $user->phone_number ?: '—' }}</td>
                                        <td>
                                            @if($user->staff)<span class="badge bg-info">Staff</span>@endif
                                            @if($user->parent_id)<span class="badge bg-secondary">Parent</span>@endif
                                        </td>
                                        <td>
                                            @if($user->google_id)
                                                <span class="text-success">Linked</span>
                                                @if($user->google_email)<div class="small text-muted">{{ $user->google_email }}</div>@endif
                                            @else
                                                <span class="text-muted">Not linked</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->google_id)
                                                <span class="text-muted">—</span>
                                            @elseif($user->google_link_required)
                                                <span class="text-warning">Marked</span>
                                            @else
                                                <span class="text-muted">Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No users in this group.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer">{{ $users->withQueryString()->links() }}</div>
                @endif
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('selectAllUsers')?.addEventListener('change', function () {
    document.querySelectorAll('.js-user-id').forEach((el) => { el.checked = this.checked; });
});
</script>
@endpush
@endsection
