@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
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
          Provision logins, send credentials (one message per family), and track first-signup progress.
          Temp password format: <code>admission-year</code> (e.g. RKS001-2026).
        </p>
      </div>
      <a href="{{ route('students.parents-contact') }}" class="btn btn-ghost-strong"><i class="bi bi-telephone"></i> Parents Contact</a>
    </div>

    @include('students.partials.alerts')

    @if(session('parent_temp_password'))
      <div class="alert alert-info">Temporary password (share securely if delivery failed): <strong>{{ session('parent_temp_password') }}</strong></div>
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
            <h5 class="mb-0">Families</h5>
            <p class="text-muted small mb-0">{{ count($rows) }} families shown (siblings counted once).</p>
          </div>
          <div class="d-flex flex-wrap gap-2 align-items-center">
            <label class="form-check-label small me-1"><input type="checkbox" name="channels[]" value="sms" class="form-check-input" checked> SMS</label>
            <label class="form-check-label small me-1"><input type="checkbox" name="channels[]" value="whatsapp" class="form-check-input"> WhatsApp</label>
            <label class="form-check-label small me-2"><input type="checkbox" name="channels[]" value="email" class="form-check-input"> Email</label>
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Send credentials to selected families? One message each.')">
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
                  <th>Child (password)</th>
                  <th>Contact</th>
                  <th>Stage</th>
                  <th>Sent / Login</th>
                  <th style="min-width:14rem">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($rows as $row)
                  <tr>
                    <td>
                      <input type="checkbox" name="parent_info_ids[]" value="{{ $row['parent_info_id'] }}" class="row-check">
                    </td>
                    <td>
                      <div class="fw-semibold">{{ $row['family_name'] }}</div>
                      <div class="text-muted small">{{ $row['children_count'] }} child(ren)</div>
                    </td>
                    <td>
                      <div>{{ $row['child_name'] ?? '—' }}</div>
                      <code class="small">{{ $row['child_admission'] ?? '—' }}</code>
                    </td>
                    <td>
                      <div class="small">{{ $row['phone'] ?? '—' }}</div>
                      <div class="small text-muted">{{ $row['email'] ?? '' }}</div>
                      @if($row['login'])
                        <div class="small">Login: {{ $row['login'] }}</div>
                      @endif
                    </td>
                    <td>
                      <span class="badge text-bg-secondary">{{ $stages[$row['stage']] ?? $row['stage'] }}</span>
                    </td>
                    <td class="small">
                      @if($row['credentials_sent_at'])
                        Sent {{ \Illuminate\Support\Carbon::parse($row['credentials_sent_at'])->format('Y-m-d H:i') }}
                        @if($row['credentials_sent_via']) ({{ $row['credentials_sent_via'] }}) @endif
                        <br>
                      @endif
                      @if($row['first_app_login_at'])
                        First login {{ \Illuminate\Support\Carbon::parse($row['first_app_login_at'])->format('Y-m-d H:i') }}
                      @else
                        <span class="text-muted">Never logged in</span>
                      @endif
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        <button form="send-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-primary" type="submit">Send</button>
                        <button form="reset-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-warning" type="submit">Reset pwd</button>
                        <button form="pin-{{ $row['parent_info_id'] }}" class="btn btn-sm btn-outline-secondary" type="submit">PIN help</button>
                      </div>
                    </td>
                  </tr>
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
