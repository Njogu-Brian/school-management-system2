<div class="row">
    <div class="col-md-4 mb-3">
        <label>Exam Type</label>
        <input type="text" name="exam_type" class="form-control" value="{{ old('exam_type', $grade->exam_type ?? '') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label>Grade</label>
        <input type="text" name="grade_name" class="form-control" value="{{ old('grade_name', $grade->grade_name ?? '') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label>From %</label>
        <input type="number" step="0.01" name="percent_from" class="form-control" value="{{ old('percent_from', $grade->percent_from ?? '') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label>To %</label>
        <input type="number" step="0.01" name="percent_upto" class="form-control" value="{{ old('percent_upto', $grade->percent_upto ?? '') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label>Point</label>
        <input type="number" step="0.01" name="grade_point" class="form-control" value="{{ old('grade_point', $grade->grade_point ?? '') }}">
    </div>
    <div class="col-md-12 mb-3">
        <label>Description</label>
        <input type="text" name="description" class="form-control" value="{{ old('description', $grade->description ?? '') }}">
    </div>
</div>
