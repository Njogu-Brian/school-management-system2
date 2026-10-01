@extends('operator.layout')
@section('title', 'Create school')
@section('content')
<h1 class="h3 mb-3">Create school</h1>
@if(!$cpanelReady)
    <div class="alert alert-warning">cPanel UAPI is not configured. Tick <strong>Manual database</strong> and paste credentials for a DB you created in cPanel, or set CPANEL_* in .env.</div>
@endif
<form method="post" action="{{ route('operator.schools.store') }}" class="card p-4">
    @csrf
    <h2 class="h6 text-uppercase text-muted">School</h2>
    <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="col-md-3"><label class="form-label">Slug</label><input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="auto"></div>
        <div class="col-md-3"><label class="form-label">App code</label><input name="code" class="form-control" value="{{ old('code') }}" placeholder="auto e.g. STM001"></div>
        <div class="col-md-6"><label class="form-label">Contact email</label><input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}"></div>
        <div class="col-md-6"><label class="form-label">Contact phone</label><input name="contact_phone" class="form-control" value="{{ old('contact_phone') }}"></div>
        <div class="col-md-3"><label class="form-label">Primary colour</label><input name="primary_color" class="form-control" value="{{ old('primary_color', '#0d6efd') }}"></div>
        <div class="col-md-3"><label class="form-label">Secondary colour</label><input name="secondary_color" class="form-control" value="{{ old('secondary_color', '#198754') }}"></div>
        <div class="col-md-3"><label class="form-label">Monthly fee (KES)</label><input type="number" step="0.01" name="monthly_fee" class="form-control" value="{{ old('monthly_fee', $defaultFee) }}"></div>
    </div>
    <h2 class="h6 text-uppercase text-muted">First Super Admin</h2>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Name</label><input name="admin_name" class="form-control" value="{{ old('admin_name') }}" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input name="admin_phone" class="form-control" value="{{ old('admin_phone') }}"></div>
    </div>
    <h2 class="h6 text-uppercase text-muted">Database</h2>
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="manual_db" value="1" id="manual_db" {{ old('manual_db') ? 'checked' : '' }}
               onchange="document.getElementById('manual-fields').classList.toggle('d-none', !this.checked)">
        <label class="form-check-label" for="manual_db">Use manually created MySQL database</label>
    </div>
    <div id="manual-fields" class="row g-3 mb-3 {{ old('manual_db') ? '' : 'd-none' }}">
        <div class="col-md-4"><label class="form-label">DB name</label><input name="db_name" class="form-control" value="{{ old('db_name') }}"></div>
        <div class="col-md-4"><label class="form-label">DB user</label><input name="db_username" class="form-control" value="{{ old('db_username') }}"></div>
        <div class="col-md-4"><label class="form-label">DB password</label><input type="password" name="db_password" class="form-control"></div>
    </div>
    <button class="btn btn-primary">Provision school</button>
</form>
@endsection
