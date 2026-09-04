@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
      .cred-child-table { margin: 0; }
      .cred-child-table td, .cred-child-table th { vertical-align: middle; }
      .cred-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.85rem; user-select: all; }
      .cred-user-list { display: flex; flex-direction: column; gap: 0.2rem; }
    </style>
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Parent Credentials' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Parent Credentials</h1>
        <p class="text-muted mb-0">
          Username is the parent phone number (email only if there is no phone). Father and mother each have their own username.
          Password is the child’s admission number and full year, e.g. <code>RKS001-2026</code>. Siblings can use any child’s password.
        </p>
      </div>
      <a href="{{ route('students.parents-contact') }}" class="btn btn-ghost-strong"><i class="bi bi-telephone"></i> Parents Contact</a>
    </div>

    @include('students.partials.alerts')

    @if(session('parent_temp_password'))
      <div class="alert alert-info">Password to share: <strong class="cred-mono">{{ session('parent_temp_password') }}</strong></div>
    @endif

    <div class="row g-2 mb-3">
      @foreach($stages as $key => $label)
        <div class="col-6 col-md">
          <a href="{{ route('students.parent-credentials', array_filter(['stage' => $key, 'q' => $search])) }}"
             class="settings-card d-block text-decoration-none p-3 {{ $stage === $key ? 'border border-primary' : '' }}">
            <div class="text-muted small">{{ $label }}</div>
            <div class="fs-4 fw-semibold">{{ $counts[$key] ?? 0 }}</div>
          </a>
        </div>
      @endforeach
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('students.parent-credentials') }}">
          <div class="col-md-5">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Name, phone, admission…">
          </div>
          <div class="col-md-3">
            <label class="form-label">Stage</label>
            <select name="stage" class="form-select">
              <option value="">All stages</option>
              @foreach($stages as $key => $label)
                <option value="{{ $key }}" @selected($stage === $key)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('students.parent-credentials') }}">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <form method="POST" action="{{ route('students.parent-credentials.bulk-send') }}" id="bulkForm">
      @csrf
      <div class="settings-card mb-3">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Children</h5>
            <p class="text-muted small mb-0">{{ collect($rows)->sum('children_count') }} child row(s) in {{ count($rows) }} families.</p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <label class="form-check-label small me-1"><input type="checkbox" name="channels[]" value="sms" class="form-check-input" checked> SMS</label>
            <label class="form-check-label small me-1"><input type="checkbox" name="channels[]" value="whatsapp" class="form-check-input"> WhatsApp</label>
            <label class="form-check-label small me-2"><input type="checkbox" name="channels[]" value="email" class="form-check-input"> Email</label>
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Send credentials to selected families? Father and mother each get their own username.')">
              Bulk send selected
            </button>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
              <thead>
                <tr>
                  <th style="width:2rem"><input type="checkbox" id="checkAll"></th>
                  <th>Family</th>
                  <th>Child</th>
                  <th>Username</th>
                  <th>Password</th>
                  <th>Stage</th>
                  <th style="min-width:14rem">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $row)
                  @php
                    $children = $row['children'] ?? [];
                    if ($children === []) {
                        $children = [[
                            'id' => null,
                            'name' => $row['child_name'] ?? '—',
                            'admission_number' => $row['child_admission'] ?? null,
                            'password' => null,
                            'class_name' => null,
                        ]];
                    }
                    $usernames = collect($row['accounts'] ?? [])->map(function ($account) {
                        $username = $account['username'] ?? $account['login'] ?? $account['contact'] ?? null;
                        return [
                            'label' => $account['label'] ?? 'Parent',
                            'name' => $account['name'] ?? null,
                            'username' => $username,
                        ];
                    })->all();
                  @endphp
                  @foreach($children as $index => $child)
                    <tr>
                      @if($index === 0)
                        <td rowspan="{{ count($children) }}">
                          <input type="checkbox" name="parent_info_ids[]" value="{{ $row['parent_info_id'] }}" class="row-check">
                        </td>
                        <td rowspan="{{ count($children) }}">
                          <div class="fw-semibold">{{ $row['family_name'] }}</div>
                          <div class="text-muted small">{{ $row['children_count'] }} child(ren)</div>
                        </td>
                      @endif
                      <td>
                        <div class="fw-semibold">{{ $child['name'] ?: '—' }}</div>
                        <div class="text-muted small">
                          {{ $child['admission_number'] ?? 'No admission' }}
                          @if(!empty($child['class_name']))
                            · {{ $child['class_name'] }}
                          @endif
                        </div>
                      </td>
                      <td>
                        @if($usernames === [])
                          <span class="text-muted">No parent phone or email</span>
                        @else
                          <div class="cred-user-list">
                            @foreach($usernames as $login)
                              <div>
                                <span class="text-muted">{{ $login['label'] }}@if($login['name']) · {{ $login['name'] }}@endif</span>
                                <div>
                                  @if($login['username'])
                                    <code class="cred-mono">{{ $login['username'] }}</code>
                                  @else
                                    <span class="text-muted">No username</span>
                                  @endif
                                </div>
                              </div>
                            @endforeach
                          </div>
                        @endif
                      </td>
                      <td>
                        @if(!empty($child['password']))
                          <code class="cred-mono">{{ $child['password'] }}</code>
                        @else
                          <span class="text-muted">—</span>
                        @endif
                      </td>
                      @if($index === 0)
                        <td rowspan="{{ count($children) }}">
                          <span class="badge text-bg-secondary">{{ $stages[$row['stage']] ?? $row['stage'] }}</span>
                        </td>
                        <td rowspan="{{ count($children) }}">
                          <div class="d-flex flex-wrap gap-1">
                            <button form="send-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-primary" type="submit">Send</button>
                            <button form="reset-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-warning" type="submit">Reset pwd</button>
                            <button form="pin-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-secondary" type="submit">PIN help</button>
                          </div>
                        </td>
                      @endif
                    </tr>
                  @endforeach
                @empty
                  <tr><td colspan="7" class="text-center text-muted py-4">No families match.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </form>

    @foreach($rows as $row)
      <form id="send-{{ $row['parent_info_id'] }}" method="POST" action="{{ route('students.parent-credentials.send') }}" class="d-none">
        @csrf
        <input type="hidden" name="parent_info_id" value="{{ $row['parent_info_id'] }}">
        <input type="hidden" name="channels[]" value="sms">
      </form>
      <form id="reset-{{ $row['parent_info_id'] }}" method="POST" action="{{ route('students.parent-credentials.reset-password') }}" class="d-none">
        @csrf
        <input type="hidden" name="parent_info_id" value="{{ $row['parent_info_id'] }}">
        <input type="hidden" name="share" value="1">
        <input type="hidden" name="channels[]" value="sms">
      </form>
      <form id="pin-{{ $row['parent_info_id'] }}" method="POST" action="{{ route('students.parent-credentials.pin-help') }}" class="d-none">
        @csrf
        <input type="hidden" name="parent_info_id" value="{{ $row['parent_info_id'] }}">
        <input type="hidden" name="channels[]" value="sms">
      </form>
    @endforeach
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
  document.querySelectorAll('.row-check').forEach(cb => { cb.checked = this.checked; });
});
</script>
@endpush
