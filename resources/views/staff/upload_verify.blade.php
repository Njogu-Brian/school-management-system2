@extends('layouts.app')

@section('content')
<div class="container">
  <h1 class="mb-4">Verify Staff Upload</h1>

  @if(session('errors'))
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach(session('errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('staff.upload.commit') }}">
    @csrf

    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Work Email</th>
            <th>Phone</th>
            <th>Department</th>
            <th>Job Title</th>
            <th>Category</th>
            <th>Supervisor</th>
            <th>Role</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $i => $r)
            <tr>
              <td>{{ $i+1 }}</td>
              <td>{{ $r['first_name'] }} {{ $r['last_name'] }}</td>
              <td>{{ $r['work_email'] }}</td>
              <td>{{ $r['phone_number'] }}</td>

              <td>
                <select name="department_id[{{ $i }}]" class="form-select">
                  <option value="">—</option>
                  @foreach($departments as $d)
                    <option value="{{ $d->id }}"
                      @selected(strcasecmp($r['department_guess'],$d->name)==0)>{{ $d->name }}</option>
                  @endforeach
                </select>
              </td>

              <td>
                <select name="job_title_id[{{ $i }}]" class="form-select">
                  <option value="">—</option>
                  @foreach($jobTitles as $j)
                    <option value="{{ $j->id }}"
                      @selected(strcasecmp($r['job_title_guess'],$j->name)==0)>{{ $j->name }}</option>
                  @endforeach
                </select>
              </td>

              <td>
                <select name="staff_category_id[{{ $i }}]" class="form-select">
                  <option value="">—</option>
                  @foreach($categories as $c)
                    <option value="{{ $c->id }}"
                      @selected(strcasecmp($r['category_guess'],$c->name)==0)>{{ $c->name }}</option>
                  @endforeach
                </select>
              </td>

              <td>
                <select name="supervisor_id[{{ $i }}]" class="form-select">
                  <option value="">—</option>
                  @foreach($supervisors as $s)
                    <option value="{{ $s->id }}"
                      @selected($r['supervisor_staff_id_guess'] === $s->staff_id)>
                      {{ $s->staff_id }} — {{ $s->first_name }} {{ $s->last_name }}
                    </option>
                  @endforeach
                </select>
              </td>

              <td>
                <select name="spatie_role_name[{{ $i }}]" class="form-select">
                  <option value="">—</option>
                  @foreach($roles as $role)
                    <option value="{{ $role->name }}"
                      @selected(strcasecmp($r['spatie_role_guess'],$role->name)==0)>
                      {{ $role->name }}
                    </option>
                  @endforeach
                </select>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-3 d-flex gap-2">
      <a href="{{ route('staff.upload.form') }}" class="btn btn-secondary">← Re-upload</a>
      <button class="btn btn-primary">✅ Confirm & Import</button>
    </div>
  </form>
</div>
@endsection
