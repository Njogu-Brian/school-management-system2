@extends('layouts.app')

@section('title', 'App adoption')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="h3 mb-1">App adoption</h1>
      <p class="text-muted mb-0">Track staff/parents who have signed into the mobile apps vs those who have not.</p>
    </div>
    <a href="{{ route('communication.app-issues') }}" class="btn btn-outline-secondary">Crash logs</a>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Total</div><div class="fs-4 fw-bold">{{ $summary['total'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Never signed in</div><div class="fs-4 fw-bold">{{ $summary['never'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Has used app</div><div class="fs-4 fw-bold">{{ $summary['used'] }}</div></div></div>
    <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Active ({{ $days }}d)</div><div class="fs-4 fw-bold">{{ $summary['active'] }}</div></div></div>
  </div>

  <form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Audience</label>
        <select name="audience" class="form-select">
          <option value="staff" @selected($audience==='staff')>Staff / teachers</option>
          <option value="parents" @selected($audience==='parents')>Parents</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="all" @selected($status==='all')>All</option>
          <option value="never" @selected($status==='never')>Never signed in</option>
          <option value="used" @selected($status==='used')>Has used app</option>
          <option value="active" @selected($status==='active')>Active recently</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Active days</label>
        <input type="number" min="1" max="90" name="days" value="{{ $days }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Search</label>
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Name or email">
      </div>
      <div class="col-md-1">
        <button class="btn btn-primary w-100" type="submit">Filter</button>
      </div>
    </div>
    <div class="mt-2">
      <button class="btn btn-sm btn-outline-secondary" name="export" value="1" type="submit">Export CSV</button>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Last login</th>
            <th>Last seen</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $user)
            <tr>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->getRoleNames()->implode(', ') }}</td>
              <td>{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
              <td>{{ $user->last_seen_at?->format('Y-m-d H:i') ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No users match.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $users->links() }}</div>
  </div>
</div>
@endsection
