<form method="GET" class="card card-body shadow-sm mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-3">
      <label class="form-label">Academic Year</label>
      <select name="year_id" class="form-select">@foreach($years as $y)
        <option value="{{ $y->id }}" @selected($filters['year_id']==$y->id)>{{ $y->name }}</option>
      @endforeach</select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label">Term</label>
      <select name="term_id" class="form-select">@foreach($terms as $t)
        <option value="{{ $t->id }}" @selected($filters['term_id']==$t->id)>{{ $t->name }}</option>
      @endforeach</select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label">From</label>
      <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}">
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label">To</label>
      <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}">
    </div>

    <div class="col-6 col-md-3">
      <label class="form-label">Classroom</label>
      <select name="classroom_id" class="form-select">
        <option value="">All</option>
        @foreach($classrooms as $c)
        <option value="{{ $c->id }}" @selected($filters['classroom_id']==$c->id)>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-6 col-md-3">
      <label class="form-label">Stream</label>
      <select name="stream_id" class="form-select">
        <option value="">All</option>
        @foreach($streams as $s)
          <option value="{{ $s->id }}" @selected($filters['stream_id']==$s->id)>{{ $s->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-12 col-md-3">
      <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Apply Filters</button>
    </div>
  </div>
</form>
