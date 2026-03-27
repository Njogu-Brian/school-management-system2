<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

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
            'default_min_mark' => 'nullable|numeric|min:0',
            'default_max_mark' => 'nullable|numeric|min:1|gte:default_min_mark',
        ]);

        ExamType::create($data + ['calculation_method' => 'average']);
        return back()->with('success','Exam type created.');
    }

    public function update(Request $r, ExamType $type)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:exam_types,code,'.$type->id,
            'default_min_mark' => 'nullable|numeric|min:0',
            'default_max_mark' => 'nullable|numeric|min:1|gte:default_min_mark',
        ]);

        $type->update($data);
        return back()->with('success','Exam type updated.');
    }

    public function destroy(ExamType $type)
    {
        try {
            DB::transaction(function () use ($type) {
                // Free dependent records first to avoid FK errors.
                $type->groups()->update(['exam_type_id' => null]);
                Exam::where('exam_type_id', $type->id)->update(['exam_type_id' => null]);
                $type->delete();
            });
        } catch (Throwable $e) {
            return back()->with('error', 'Exam type could not be deleted right now. Please try again.');
        }

        return back()->with('success','Exam type deleted.');
    }
}
