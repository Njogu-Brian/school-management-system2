@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item active">Link Siblings</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Link Siblings</h1>
    <a href="{{ route('families.index') }}" class="btn btn-outline-secondary"><i class="bi bi-people"></i> Families</a>
  </div>

  @include('students.partials.alerts')

  <div class="row">
    {{-- LEFT: choose Student A (persistent) --}}
    <div class="col-lg-5">
      <div class="card mb-3">
        <div class="card-header">1) Choose Student A</div>
        <div class="card-body">
          <div class="input-group mb-2">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="a_query" class="form-control" placeholder="Search name or admission #">
          </div>
          <div id="a_results" class="list-group small"></div>
        </div>
      </div>

      {{-- If A selected: show A summary & family members --}}
      @if($studentA)
      <div class="card">
        <div class="card-header">Selected: {{ $studentA->admission_number }}</div>
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-2">
            <img src="{{ $studentA->photo_url ?? asset('images/avatar-student.png') }}" class="avatar-44" alt="">
            <div>
              <div class="fw-semibold">{{ $studentA->first_name }} {{ $studentA->last_name }}</div>
              <div class="text-muted small">{{ optional($studentA->classroom)->name }} {{ optional($studentA->stream)->name ? '· '.$studentA->stream->name : '' }}</div>
            </div>
          </div>

          <hr>
          <div class="fw-semibold mb-2">Current family</div>
          @if($family && $family->students->count())
            <div class="vstack gap-2">
              @foreach($family->students as $st)
                <div class="d-flex justify-content-between align-items-center">
                  <span>{{ $st->admission_number }} — {{ $st->first_name }} {{ $st->last_name }}</span>
                  @if($st->id !== $studentA->id)
                  <form action="{{ route('siblings.unlink') }}" method="POST" onsubmit="return confirm('Remove {{ $st->first_name }} from this family?')">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $st->id }}">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Remove</button>
                  </form>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <div class="text-muted">No family yet.</div>
          @endif
        </div>
      </div>
      @endif
    </div>

    {{-- RIGHT: search B and link to A; repeat for C, D… --}}
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header">2) Search another student to link with @if($studentA) <span class="fw-semibold">{{ $studentA->admission_number }}</span> @endif</div>
        <div class="card-body">
          @if(!$studentA)
            <div class="alert alert-info">Pick <strong>Student A</strong> first.</div>
          @else
            <form action="{{ route('siblings.link.store') }}" method="POST" class="mb-3">
              @csrf
              <input type="hidden" name="student_a_id" value="{{ $studentA->id }}">
              <div class="input-group mb-2">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="b_query" class="form-control" placeholder="Search Student B (name / admission #)">
              </div>
              <div id="b_results" class="list-group small mb-3"></div>

              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" value="1" id="confirm_merge" name="confirm_merge">
                <label class="form-check-label" for="confirm_merge">Confirm merge if they belong to different families</label>
              </div>

              {{-- When the user clicks a result, we inject a hidden input student_b_id --}}
              <div id="selectedB" class="mb-2"></div>

              <button type="submit" class="btn btn-primary" id="linkBtn" disabled>
                <i class="bi bi-link-45deg"></i> Link Selected with {{ $studentA->admission_number }}
              </button>
            </form>
            <div class="text-muted small">
              After linking B, this page reloads with the same Student A so you can immediately search & link C, D, …
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Helpers
  async function searchStudents(q) {
    const res = await fetch(`{{ route('api.students.search') }}?q=`+encodeURIComponent(q));
    return await res.json();
  }

  // Pick A: redirects with ?a=<id> so left side persists across links
  (function(){
    const q = document.getElementById('a_query');
    const box = document.getElementById('a_results');
    let t=null;
    q?.addEventListener('input', ()=>{
      clearTimeout(t);
      t = setTimeout(async ()=>{
        const val = q.value.trim(); box.innerHTML='';
        if (!val) return;
        const rows = await searchStudents(val);
        rows.forEach(r=>{
          const a = document.createElement('a');
          a.href = '{{ route('siblings.link') }}?a='+r.id;
          a.className = 'list-group-item list-group-item-action';
          a.textContent = `${r.admission_number} — ${r.full_name}`;
          box.appendChild(a);
        });
      }, 300);
    });
  })();

  // Pick B: stays on page; sets hidden input and enables submit
  (function(){
    const q = document.getElementById('b_query');
    const box = document.getElementById('b_results');
    const sel = document.getElementById('selectedB');
    const btn = document.getElementById('linkBtn');
    let t=null;
    q?.addEventListener('input', ()=>{
      clearTimeout(t);
      t = setTimeout(async ()=>{
        const val = q.value.trim(); box.innerHTML=''; sel.innerHTML=''; btn.disabled = true;
        if (!val) return;
        const rows = await searchStudents(val);
        rows.forEach(r=>{
          const a = document.createElement('a');
          a.href = '#';
          a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
          a.innerHTML = `<span>${r.admission_number} — ${r.full_name}</span><span class="badge bg-primary">Select</span>`;
          a.addEventListener('click', e=>{
            e.preventDefault();
            sel.innerHTML = `
              <div class="alert alert-secondary py-2 px-3">
                <input type="hidden" name="student_b_id" value="${r.id}">
                Selected B: <strong>${r.admission_number} — ${r.full_name}</strong>
              </div>`;
            btn.disabled = false;
          });
          box.appendChild(a);
        });
      }, 300);
    });
  })();
</script>
@endpush
