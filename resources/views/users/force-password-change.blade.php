@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header">
            <div class="crumb">Users / Security</div>
            <h1>Require password change</h1>
            <p>Ask one person, several people, or everyone in staff or parents to set a new password the next time they sign in — on the web portal and on the mobile app.</p>
        </div>

        @include('partials.alerts')

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

        <form method="POST" action="{{ route('users.require-password-change.store') }}">
            @csrf
            <input type="hidden" name="group" value="{{ $group }}">
            <input type="hidden" name="q" value="{{ $search }}">

            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">{{ ucfirst($group === 'all' ? 'Staff and parents' : $group) }} <span class="input-chip">{{ $users->total() }}</span></h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-danger" type="submit" name="apply_to" value="selected" onclick="return confirm('Selected users must change password on next login?')">
                            Require for selected
                        </button>
                        <button class="btn btn-settings-primary" type="submit" name="apply_to" value="all_matching" onclick="return confirm('Everyone in this {{ $group }} list must change password on next login?')">
                            Require for everyone in this list
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
                                    <th>Status</th>
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
                                            @if($user->must_change_password)
                                                <span class="text-warning">Will change on next login</span>
                                            @else
                                                <span class="text-muted">Normal</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No users in this group.</td></tr>
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
@endsection

@push('scripts')
<script>
    document.getElementById('selectAllUsers')?.addEventListener('change', function () {
        document.querySelectorAll('.js-user-id').forEach((el) => { el.checked = this.checked; });
    });
</script>
@endpush
