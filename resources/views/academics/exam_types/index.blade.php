@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-0">Exam Types</h3>
      <small class="text-muted">Define calculation/grading defaults for exam groups and exams.</small>
    </div>
  </div>

  @includeIf('partials.alerts')

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                    <td>
                      {{ is_null($t->default_min_mark) ? '—' : $t->default_min_mark }}
                      /
                      {{ is_null($t->default_max_mark) ? '—' : $t->default_max_mark }}
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                              data-bs-target="#editTypeModal{{ $t->id }}"><i class="bi bi-pencil"></i></button>
                      <form action="{{ route('academics.exams.types.destroy', $t->id) }}" method="post" class="d-inline"
                            onsubmit="return confirm('Delete this exam type?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>

                  {{-- Edit modal --}}
                  <div class="modal fade" id="editTypeModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
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
                          <div class="modal-footer">
                            <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save Changes</button>
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
      <div class="card shadow-sm">
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
              <button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create Type</button>
            </div>
          </form>
        </div>
      </div>

      <div class="small text-muted mt-2">
        Tip: Link an exam group to a type to inherit grading defaults.
      </div>
    </div>
  </div>
</div>
@endsection
