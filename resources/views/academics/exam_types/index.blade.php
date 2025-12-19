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
        <h1 class="mb-1">Exam Types</h1>
        <p class="text-muted mb-0">Define calculation and grading defaults for exam groups and exams.</p>
      </div>
    </div>

    @includeIf('partials.alerts')

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="settings-card h-100">
          <div class="card-header">
            <h5 class="mb-0">Types</h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Method</th>
                    <th>Min / Max</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($types as $t)
                    <tr>
                      <td>{{ $t->id }}</td>
                      <td class="fw-semibold">{{ $t->name }}</td>
                      <td><code>{{ $t->code }}</code></td>
                      <td>{{ ucfirst($t->calculation_method) }}</td>
                      <td>{{ is_null($t->default_min_mark) ? '—' : $t->default_min_mark }} / {{ is_null($t->default_max_mark) ? '—' : $t->default_max_mark }}</td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                          <button class="btn btn-sm btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#editTypeModal{{ $t->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                          <form action="{{ route('academics.exams.types.destroy', $t->id) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this exam type?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>

                    <div class="modal fade" id="editTypeModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content settings-card mb-0">
                          <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title">Edit Type: {{ $t->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <form method="post" action="{{ route('academics.exams.types.update', $t->id) }}">
                            @csrf @method('PUT')
                            <div class="modal-body">
                              <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input name="name" class="form-control" required value="{{ $t->name }}">
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input name="code" class="form-control" required value="{{ $t->code }}">
                              </div>
                              <div class="mb-3">
                                <label class="form-label">Calculation Method</label>
                                <select name="calculation_method" class="form-select" required>
                                  @foreach(['average','sum','weighted','best_of','pass_fail','cbc'] as $m)
                                    <option value="{{ $m }}" @selected($t->calculation_method==$m)>{{ ucfirst($m) }}</option>
                                  @endforeach
                                </select>
                              </div>
                              <div class="row">
                                <div class="col-md-6 mb-3">
                                  <label class="form-label">Default Min Mark</label>
                                  <input type="number" step="0.01" min="0" name="default_min_mark" class="form-control" value="{{ $t->default_min_mark }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                  <label class="form-label">Default Max Mark</label>
                                  <input type="number" step="0.01" min="1" name="default_max_mark" class="form-control" value="{{ $t->default_max_mark }}">
                                </div>
                              </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                              <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
                              <button class="btn btn-settings-primary"><i class="bi bi-save2 me-1"></i>Save Changes</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>
                  @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No exam types yet. Create one →</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="settings-card">
          <div class="card-header fw-semibold">Create Type</div>
          <div class="card-body">
            <form method="post" action="{{ route('academics.exams.types.store') }}">
              @csrf
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" class="form-control" required placeholder="e.g. Average Passing">
              </div>
              <div class="mb-3">
                <label class="form-label">Code</label>
                <input name="code" class="form-control" required placeholder="e.g. avg_pass">
              </div>
              <div class="mb-3">
                <label class="form-label">Calculation Method</label>
                <select name="calculation_method" class="form-select" required>
                  @foreach(['average','sum','weighted','best_of','pass_fail','cbc'] as $m)
                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Default Min Mark</label>
                  <input type="number" step="0.01" min="0" name="default_min_mark" class="form-control" placeholder="e.g. 0">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Default Max Mark</label>
                  <input type="number" step="0.01" min="1" name="default_max_mark" class="form-control" placeholder="e.g. 100">
                </div>
              </div>
              <div class="d-grid">
                <button class="btn btn-settings-primary"><i class="bi bi-plus-circle me-1"></i>Create Type</button>
              </div>
            </form>
          </div>
        </div>
        <div class="small text-muted mt-2">Tip: Link an exam group to a type to inherit grading defaults.</div>
      </div>
    </div>
  </div>
</div>
@endsection
