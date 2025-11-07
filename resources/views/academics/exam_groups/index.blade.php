@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Exam Groups</h3>
      <small class="text-muted">Create and manage exam groups (e.g., Opener, Midterm, Endterm).</small>
    </div>
  </div>

  @includeIf('partials.alerts')

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                    <td>
                      @if($g->is_active)
                        <span class="badge bg-success">Yes</span>
                      @else
                        <span class="badge bg-secondary">No</span>
                      @endif
                    </td>
                    <td class="text-center">
                      <span class="badge text-bg-light">{{ $g->exams_count ?? 0 }}</span>
                    </td>
                    <td class="text-end">
                      <a href="{{ route('academics.exams.groups.edit', $g->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form action="{{ route('academics.exams.groups.destroy', $g->id) }}" method="post" class="d-inline"
                            onsubmit="return confirm('Delete this exam group?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                      </form>
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
      <div class="card shadow-sm">
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

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select" required>
                  <option value="">Select year</option>
                  @foreach($years as $y)
                    <option value="{{ $y->id }}" @selected(old('academic_year_id')==$y->id)>{{ $y->year }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6 mb-3">
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
              <button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create Group</button>
            </div>
          </form>
        </div>
      </div>

      <div class="small text-muted mt-2">
        Tip: After creating a group, go to <em>Manage Exams</em> to add exams under it and schedule papers.
      </div>
    </div>
  </div>
</div>
@endsection
