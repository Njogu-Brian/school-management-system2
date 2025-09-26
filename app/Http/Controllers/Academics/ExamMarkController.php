<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Exam;
use Illuminate\Http\Request;

class ExamMarkController extends Controller
{
    public function index(Request $request)
    {
        $examId = $request->query('exam_id');
        $marks = ExamMark::with(['student','subject','exam'])
            ->when($examId, fn($q)=>$q->where('exam_id',$examId))
            ->paginate(50);

        return view('academics.exam_marks.index', compact('marks','examId'));
    }

    public function edit(ExamMark $exam_mark)
    {
        return view('academics.exam_marks.edit', compact('exam_mark'));
    }

    public function update(Request $request, ExamMark $exam_mark)
    {
        $request->validate([
            'score_raw'=>'nullable|numeric|min:0|max:100',
            'remark'=>'nullable|string|max:500',
        ]);

        $exam_mark->update([
            'score_raw'=>$request->score_raw,
            'remark'=>$request->remark,
            'status'=>'submitted'
        ]);

        return redirect()->route('academics.exam-marks.index',['exam_id'=>$exam_mark->exam_id])
            ->with('success','Mark updated successfully.');
    }
}
