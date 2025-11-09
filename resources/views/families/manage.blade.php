@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('families.index') }}">Families</a></li>
      <li class="breadcrumb-item active">Family #{{ $family->id }}</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Family #{{ $family->id }}</h1>
    <a href="{{ route('families.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <div class="row">
    <div class="col-lg-5">
      {{-- Edit family details --}}
      <form action="{{ route('families.update', $family) }}" method="POST" class="card mb-3">
        @csrf @method('PUT')
        <div class="card-header">Guardian / Primary Contact</div>
        <div class="card-body vstack gap-3">
          <div>
            <label class="form-label">Guardian Name</label>
            <input type="text" class="form-control" name="guardian_name" value="{{ old('guardian_name', $family->guardian_name) }}" required>
          </div>
          <div>
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" value="{{ old('phone', $family->phone) }}">
          </div>
          <div>
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="{{ old('email', $family->email) }}">
          </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
          <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
        </div>
      </form>

      {{-- Attach student --}}
      <div class="card">
        <div class="card-header">Add Student to Family</div>
        <div class="card-body">
          <div class="input-group mb-2">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="s_query" class="form-control" placeholder="Search student (name or admission #)">
          </div>
          <div id="s_results" class="list-group small"></div>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      {{-- Members list --}}
      <div class="card">
        <div class="card-header">Family Members ({{ $family->students->count() }})</div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Admission</th>
                <th>Student</th>
                <th>Class</th>
                <th>Stream</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($family->students as $st)
                <tr>
                  <td class="fw-semibold">{{ $st->admission_number }}</td>
                  <td>
                    <a href="{{ route('students.show', $st->id) }}" class="fw-semibold">
                      {{ $st->first_name }} {{ $st->last_name }}
                    </a>
                    <div class="text-muted small">DOB: {{ $st->dob ?: '—' }}</div>
                  </td>
                  <td>{{ $st->classroom->name ?? '—' }}</td>
                  <td>{{ $st->stream->name ?? '—' }}</td>
                  <td class="text-end">
                    <form action="{{ route('families.detach', $family) }}" method="POST" onsubmit="return confirm('Remove this student from the family?')">
                      @csrf
                      <input type="hidden" name="student_id" value="{{ $st->id }}">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Remove</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No members yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Student search + attach
  (function(){
    const q = document.getElementById('s_query');
    const box = document.getElementById('s_results');
    let t=null;

    q?.addEventListener('input', ()=>{
      clearTimeout(t);
      t = setTimeout(async ()=>{
        const val = q.value.trim();
        if (!val){ box.innerHTML=''; return; }
        const res = await fetch(`{{ route('api.students.search') }}?q=`+encodeURIComponent(val));
        const rows = await res.json();
        box.innerHTML = '';
        rows.forEach(r=>{
          const a = document.createElement('a');
          a.href = '#';
          a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
          a.innerHTML = `<span>${r.admission_number} — ${r.full_name}</span><button class="btn btn-sm btn-outline-primary">Add</button>`;
          a.addEventListener('click', async (e)=>{
            e.preventDefault();
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `{{ route('families.attach', $family) }}`;
            form.innerHTML = `
              @csrf
              <input type="hidden" name="student_id" value="${r.id}">
            `;
            document.body.appendChild(form);
            form.submit();
          });
          box.appendChild(a);
        });
      }, 300);
    });
  })();
</script>
@endpush
