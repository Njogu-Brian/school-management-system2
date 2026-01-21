@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
@php use Illuminate\Support\Str; @endphp
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Profile Update Links</h1>
        <p class="text-muted mb-0">Shareable family links for parent-led profile updates.</p>
      </div>
      <form action="{{ route('family-update.admin.reset-all') }}" method="POST" onsubmit="return confirm('Reset all profile update links? All families will receive new tokens.');">
        @csrf
        <button class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise"></i> Reset All Links</button>
      </form>
    </div>

    @include('students.partials.alerts')

    @if(isset($stats))
    <div class="row g-3 mb-3">
      <div class="col-md-3">
        <div class="settings-card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <div class="bg-primary bg-opacity-10 rounded p-3">
                  <i class="bi bi-link-45deg text-primary fs-4"></i>
                </div>
              </div>
              <div class="flex-grow-1 ms-3">
                <div class="text-muted small">Total Links</div>
                <div class="h4 mb-0">{{ $stats['total_links'] }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <div class="bg-success bg-opacity-10 rounded p-3">
                  <i class="bi bi-cursor-click text-success fs-4"></i>
                </div>
              </div>
              <div class="flex-grow-1 ms-3">
                <div class="text-muted small">Total Clicks</div>
                <div class="h4 mb-0">{{ number_format($stats['total_clicks']) }}</div>
                <div class="text-muted small">{{ $stats['links_with_clicks'] }} links clicked</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <div class="bg-info bg-opacity-10 rounded p-3">
                  <i class="bi bi-pencil-square text-info fs-4"></i>
                </div>
              </div>
              <div class="flex-grow-1 ms-3">
                <div class="text-muted small">Total Updates</div>
                <div class="h4 mb-0">{{ number_format($stats['total_updates']) }}</div>
                <div class="text-muted small">{{ $stats['links_with_updates'] }} links updated</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="settings-card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <div class="bg-warning bg-opacity-10 rounded p-3">
                  <i class="bi bi-check-circle text-warning fs-4"></i>
                </div>
              </div>
              <div class="flex-grow-1 ms-3">
                <div class="text-muted small">Active Links</div>
                <div class="h4 mb-0">{{ $stats['active_links'] }}</div>
                <div class="text-muted small">of {{ $stats['total_links'] }} total</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Guardian / Contact</th>
                <th>Students</th>
                <th>Token</th>
                <th>Clicks</th>
                <th>Updates</th>
                <th>Last Activity</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($families as $family)
                <tr>
                  <td>{{ $family->id }}</td>
                  <td>
                    <div class="fw-semibold">{{ $family->guardian_name ?? '—' }}</div>
                    <div class="text-muted small">
                      {{ $family->phone ?? 'No phone' }} @if($family->email) · {{ $family->email }} @endif
                    </div>
                  </td>
                  <td>{{ $family->students_count }}</td>
                  <td>
                    @if($family->updateLink)
                      <span class="badge bg-light text-dark">{{ $family->updateLink->token }}</span>
                    @else
                      <span class="text-muted small">Not generated</span>
                    @endif
                  </td>
                  <td>
                    @if($family->updateLink)
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info">{{ $family->updateLink->click_count ?? 0 }}</span>
                        @if($family->updateLink->first_clicked_at)
                          <small class="text-muted" title="First clicked: {{ $family->updateLink->first_clicked_at->format('M d, Y H:i') }}">
                            <i class="bi bi-calendar3"></i> {{ $family->updateLink->last_clicked_at?->diffForHumans() ?? 'Never' }}
                          </small>
                        @else
                          <small class="text-muted">Never clicked</small>
                        @endif
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if($family->updateLink)
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success">{{ $family->updateLink->update_count ?? 0 }}</span>
                        @if($family->updateLink->last_updated_at)
                          <small class="text-muted" title="Last updated: {{ $family->updateLink->last_updated_at->format('M d, Y H:i') }}">
                            {{ $family->updateLink->last_updated_at->diffForHumans() }}
                          </small>
                        @else
                          <small class="text-muted">Never updated</small>
                        @endif
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>
                    @if($family->updateLink)
                      @if($family->updateLink->last_clicked_at || $family->updateLink->last_updated_at)
                        @php
                          $lastActivity = max(
                            $family->updateLink->last_clicked_at?->timestamp ?? 0,
                            $family->updateLink->last_updated_at?->timestamp ?? 0
                          );
                          $lastActivityDate = \Carbon\Carbon::createFromTimestamp($lastActivity);
                        @endphp
                        <small class="text-muted">{{ $lastActivityDate->diffForHumans() }}</small>
                      @else
                        <small class="text-muted">No activity</small>
                      @endif
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                      @if($family->updateLink)
                        <button type="button" class="btn btn-sm btn-ghost-strong" onclick="navigator.clipboard.writeText('{{ route('family-update.form', $family->updateLink->token) }}'); this.innerText='Copied'; setTimeout(()=>this.innerText='Copy',1200);">
                          <i class="bi bi-clipboard"></i> Copy
                        </button>
                        <a class="btn btn-sm btn-ghost-strong" target="_blank" href="{{ route('family-update.form', $family->updateLink->token) }}">
                          <i class="bi bi-box-arrow-up-right"></i> Open
                        </a>
                        <form action="{{ route('families.update-link.reset', $family) }}" method="POST" onsubmit="return confirm('Reset link for this family? A new link will be issued.');">
                          @csrf
                          <button class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                          </button>
                        </form>
                      @else
                        <form action="{{ route('families.update-link.show', $family) }}" method="GET">
                          <button class="btn btn-sm btn-settings-primary">
                            <i class="bi bi-link-45deg"></i> Generate
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">No families found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">
        {{ $families->links() }}
      </div>
    </div>

    <div class="settings-card mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Profile Updates</h5>
        <span class="text-muted small">Paginated change history</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>When</th>
                <th>Family</th>
                <th>Student</th>
                <th>Changed By</th>
                <th>Field</th>
                <th>Before</th>
                <th>After</th>
                <th>Source</th>
              </tr>
            </thead>
            <tbody>
              @forelse($audits as $audit)
                <tr>
                  <td class="text-muted small">{{ $audit->created_at?->format('M d, Y H:i') }}</td>
                  <td>#{{ $audit->family_id }}</td>
                  <td>{{ optional($audit->student)->full_name }}</td>
                  <td>
                    @if($audit->user)
                      <div class="fw-semibold">{{ $audit->user->name }}</div>
                      <small class="text-muted">{{ $audit->user->email }}</small>
                    @else
                      <span class="badge bg-secondary">Public (Parent)</span>
                    @endif
                  </td>
                  <td><code class="small">{{ $audit->field }}</code></td>
                  <td class="text-muted small">{{ Str::limit($audit->before ?? '—', 60) }}</td>
                  <td class="text-muted small">{{ Str::limit($audit->after ?? '—', 60) }}</td>
                  <td>
                    <span class="badge bg-{{ $audit->source === 'admin' ? 'primary' : 'success' }}">
                      {{ ucfirst($audit->source) }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">No updates recorded yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="card-footer d-flex justify-content-end">
      {{ $audits->links() }}
    </div>

  </div>
</div>
@endsection

