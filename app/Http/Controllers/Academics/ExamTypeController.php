<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamType;
use Illuminate\Http\Request;

class ExamTypeController extends Controller
{
    public function index()
    {
        return view('academics.exam_types.index', [
            'types' => ExamType::orderBy('name')->get()
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:exam_types,code',
            'calculation_method' => 'required|in:average,sum,weighted,best_of,pass_fail,cbc',
            'default_min_mark' => 'nullable|numeric|min:0',
            'default_max_mark' => 'nullable|numeric|min:1',
        ]);
        ExamType::create($data);
        return back()->with('success','Exam type created.');
    }

    public function update(Request $r, ExamType $type)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:exam_types,code,'.$type->id,
            'calculation_method' => 'required|in:average,sum,weighted,best_of,pass_fail,cbc',
            'default_min_mark' => 'nullable|numeric|min:0',
            'default_max_mark' => 'nullable|numeric|min:1',
        ]);
        $type->update($data);
        return back()->with('success','Exam type updated.');
    }

    public function destroy(ExamType $type)
    {
        $type->delete();
        return back()->with('success','Exam type deleted.');
    }
}
