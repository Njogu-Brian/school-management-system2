@extends('layouts.app')

@section('title', 'App crash logs')

@section('content')
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h1 class="h3 mb-1">App crash logs</h1>
      <p class="text-muted mb-0">Client issues reported from Users and Admin mobile apps.</p>
    </div>
    <a href="{{ route('communication.app-adoption') }}" class="btn btn-outline-secondary">App adoption</a>
  </div>

  <form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">App</label>
        <select name="app" class="form-select">
          <option value="" @selected(($app ?? '')==='')>All</option>
          <option value="users" @selected(($app ?? '')==='users')>Users</option>
          <option value="admin" @selected(($app ?? '')==='admin')>Admin</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary" type="submit">Filter</button>
      </div>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>When</th>
            <th>App</th>
            <th>User</th>
            <th>Message</th>
            <th>Platform</th>
          </tr>
        </thead>
        <tbody>
          @forelse($issues as $issue)
            <tr>
              <td class="text-nowrap">{{ $issue->created_at?->format('Y-m-d H:i') }}</td>
              <td>{{ $issue->app }}</td>
              <td>{{ $issue->user?->name ?? 'Anonymous' }}</td>
              <td>
                <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($issue->message, 120) }}</div>
                @if($issue->stack)
                  <details class="small text-muted mt-1"><summary>Stack</summary><pre class="small mb-0">{{ \Illuminate\Support\Str::limit($issue->stack, 2000) }}</pre></details>
                @endif
              </td>
              <td>{{ $issue->platform }} {{ $issue->app_version }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No issues yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $issues->links() }}</div>
  </div>
</div>
@endsection
