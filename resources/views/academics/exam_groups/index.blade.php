@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Exam Groups</h1>
        <p class="text-muted mb-0">Create and manage exam groups (e.g., Opener, Midterm, Endterm).</p>
      </div>
    </div>

    @includeIf('partials.alerts')

    <div class="row g-3">
      <div class="col-lg-8">
        <div class="settings-card h-100">
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>AY / Term</th>
                    <th>Active</th>
                    <th class="text-center">Exams</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($groups as $g)
                    <tr>
                      <td>{{ $g->id }}</td>
                      <td class="fw-semibold">{{ $g->name }}</td>
                      <td>{{ $g->type?->name ?? '—' }}</td>
                      <td>{{ $g->academicYear?->year ?? '—' }} / {{ $g->term?->name ?? '—' }}</td>
                      <td>@if($g->is_active)<span class="pill-badge pill-success">Yes</span>@else<span class="pill-badge pill-muted">No</span>@endif</td>
                      <td class="text-center"><span class="pill-badge pill-secondary">{{ $g->exams_count ?? 0 }}</span></td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                          <a href="{{ route('academics.exams.groups.edit', $g->id) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                          <form action="{{ route('academics.exams.groups.destroy', $g->id) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this exam group?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No exam groups yet. Create one →</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @if(method_exists($groups,'links'))
            <div class="card-footer">{{ $groups->links() }}</div>
          @endif
        </div>
      </div>

      <div class="col-lg-4">
        <div class="settings-card">
          <div class="card-header fw-semibold">Create Group</div>
          <div class="card-body">
            <form method="post" action="{{ route('academics.exams.groups.index') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required placeholder="e.g. Opener Exam" value="{{ old('name') }}">
              </div>
              <div class="mb-3">
                <label class="form-label">Exam Type</label>
                <select name="exam_type_id" class="form-select" required>
                  <option value="">Select type</option>
                  @foreach($types as $t)
                    <option value="{{ $t->id }}" @selected(old('exam_type_id')==$t->id)>{{ $t->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="row g-2">
                <div class="col-md-6">
                  <label class="form-label">Academic Year</label>
                  <select name="academic_year_id" class="form-select" required>
                    <option value="">Select year</option>
                    @foreach($years as $y)
                      <option value="{{ $y->id }}" @selected(old('academic_year_id')==$y->id)>{{ $y->year }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Term</label>
                  <select name="term_id" class="form-select" required>
                    <option value="">Select term</option>
                    @foreach($terms as $t)
                      <option value="{{ $t->id }}" @selected(old('term_id')==$t->id)>{{ $t->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Description (optional)</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Short description">{{ old('description') }}</textarea>
              </div>
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="groupActive" name="is_active" value="1" checked>
                <label class="form-check-label" for="groupActive">Active</label>
              </div>
              <div class="d-grid">
                <button class="btn btn-settings-primary"><i class="bi bi-plus-circle me-1"></i>Create Group</button>
              </div>
            </form>
          </div>
        </div>
        <div class="small text-muted mt-2">Tip: After creating a group, go to Manage Exams to add exams under it and schedule papers.</div>
      </div>
    </div>
  </div>
</div>
@endsection
