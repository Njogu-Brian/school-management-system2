<div class="row">
    <div class="col-md-6 mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" 
               value="{{ old('name', $exam->name ?? '') }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label>Type</label>
        <select name="type" class="form-control" required>
            @foreach(['cat','midterm','endterm','sba','mock','quiz'] as $t)
                <option value="{{ $t }}" @selected(old('type', $exam->type ?? '')==$t)>{{ strtoupper($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label>Modality</label>
        <select name="modality" class="form-control" required>
            <option value="physical" @selected(old('modality', $exam->modality ?? '')=='physical')>Physical</option>
            <option value="online" @selected(old('modality', $exam->modality ?? '')=='online')>Online</option>
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label>Academic Year</label>
        <select name="academic_year_id" class="form-control" required>
            @foreach($years as $y)
                <option value="{{ $y->id }}" @selected(old('academic_year_id', $exam->academic_year_id ?? '')==$y->id)>
                    {{ $y->year }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label>Term</label>
        <select name="term_id" class="form-control" required>
            @foreach($terms as $t)
                <option value="{{ $t->id }}" @selected(old('term_id', $exam->term_id ?? '')==$t->id)>
                    {{ $t->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label>Classroom</label>
        <select name="classroom_id" class="form-control">
            <option value="">-- None --</option>
            @foreach($classrooms as $c)
                <option value="{{ $c->id }}" @selected(old('classroom_id', $exam->classrooms_id ?? '')==$c->id)>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label>Subject</label>
        <select name="subject_id" class="form-control">
            <option value="">-- None --</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected(old('subject_id', $exam->subject_id ?? '')==$s->id)>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 mb-3">
        <label>Start Date</label>
        <input type="date" name="starts_on" class="form-control" 
               value="{{ old('starts_on', $exam->starts_on ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>End Date</label>
        <input type="date" name="ends_on" class="form-control" 
               value="{{ old('ends_on', $exam->ends_on ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>Max Marks</label>
        <input type="number" step="0.01" name="max_marks" class="form-control" 
               value="{{ old('max_marks', $exam->max_marks ?? 100) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>Weight (%)</label>
        <input type="number" step="0.01" name="weight" class="form-control" 
               value="{{ old('weight', $exam->weight ?? 100) }}">
    </div>
</div>
