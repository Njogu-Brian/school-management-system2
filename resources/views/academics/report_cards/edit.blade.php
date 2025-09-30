@extends('layouts.app')
@section('content')
<div class="container">
  <h1>Edit Report Card – {{ $report_card->student->full_name }}</h1>
  <form method="POST" action="{{ route('academics.report-cards.update',$report_card) }}">
    @csrf @method('PUT')

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Career of Interest</label>
        <input name="career_interest" value="{{ old('career_interest',$report_card->career_interest) }}" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Gifts / Talent Noticed</label>
        <input name="talent_noticed" value="{{ old('talent_noticed',$report_card->talent_noticed) }}" class="form-control">
      </div>

      <div class="col-12">
        <label class="form-label">Skills (EE / ME / AE / BE)</label>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Skill</th><th style="width:180px">Rating</th></tr></thead>
            <tbody>
              @php $i=0; @endphp
              @foreach($skillsPreset as $name)
                @php $existing = $report_card->skills->firstWhere('skill_name',$name); @endphp
                <tr>
                  <td>
                    <input type="text" class="form-control form-control-sm" name="skills[{{ $i }}][name]" value="{{ $name }}">
                  </td>
                  <td>
                    <select class="form-select form-select-sm" name="skills[{{ $i }}][rating]">
                      <option value="">-</option>
                      @foreach(['EE','ME','AE','BE'] as $b)
                        <option value="{{ $b }}" @selected(optional($existing)->rating===$b)>{{ $b }}</option>
                      @endforeach
                    </select>
                  </td>
                </tr>
                @php $i++; @endphp
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label">Class Facilitator General Remarks</label>
        <textarea name="teacher_remark" rows="4" class="form-control">{{ old('teacher_remark',$report_card->teacher_remark) }}</textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Headteacher Remark</label>
        <textarea name="headteacher_remark" rows="3" class="form-control">{{ old('headteacher_remark',$report_card->headteacher_remark) }}</textarea>
      </div>
    </div>

    <button class="btn btn-success mt-3">Save</button>
  </form>
</div>
@endsection
