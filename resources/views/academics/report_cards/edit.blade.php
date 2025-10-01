@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Report Card - {{ $report_card->student->full_name }}</h1>

    <form action="{{ route('academics.report-cards.update',$report_card) }}" method="POST">
        @csrf @method('PUT')
        
        <div class="mb-3">
            <label>Summary</label>
            <textarea name="summary" class="form-control">{{ old('summary',$report_card->summary) }}</textarea>
        </div>
        <div class="mb-3">
            <label>Career Interest</label>
            <input type="text" name="career_interest" class="form-control" value="{{ old('career_interest',$report_card->career_interest) }}">
        </div>
        <div class="mb-3">
            <label>Talent Noticed</label>
            <input type="text" name="talent_noticed" class="form-control" value="{{ old('talent_noticed',$report_card->talent_noticed) }}">
        </div>
        <div class="mb-3">
            <label>Teacher Remark</label>
            <textarea name="teacher_remark" class="form-control">{{ old('teacher_remark',$report_card->teacher_remark) }}</textarea>
        </div>
        <div class="mb-3">
            <label>Headteacher Remark</label>
            <textarea name="headteacher_remark" class="form-control">{{ old('headteacher_remark',$report_card->headteacher_remark) }}</textarea>
        </div>
        
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
