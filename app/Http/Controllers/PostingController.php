<?php

namespace App\Http\Controllers;

use App\Services\PostingService;
use Illuminate\Http\Request;

class PostingController extends Controller
{
    public function index(Request $request)
    {
        // filters form
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $streams    = \App\Models\Academics\Stream::orderBy('name')->get();
        $voteheads  = \App\Models\Votehead::orderBy('name')->get();

        return view('finance.posting.index', compact('classrooms','streams','voteheads'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'year'=>'required|integer', 'term'=>'required|in:1,2,3',
            'votehead_id'=>'nullable|exists:voteheads,id',
            'class_id'=>'nullable|exists:classrooms,id',
            'stream_id'=>'nullable|exists:streams,id',
            'student_id'=>'nullable|exists:students,id',
            'effective_date'=>'nullable|date'
        ]);
        $rows = PostingService::preview($request->all());
        return view('finance.posting.preview', ['rows'=>$rows, 'filters'=>$request->all()]);
    }

    public function commit(Request $request)
    {
        $request->validate([
            'year'=>'required|integer', 'term'=>'required|in:1,2,3',
            'activate_now'=>'required|boolean',
            'effective_date'=>'nullable|date',
            'payload'=>'required|array', // array of rows from preview to commit
        ]);

        $rows = collect($request->payload); // each row: student_id, votehead_id, amount, origin
        $count = PostingService::commit(
            $rows, (int)$request->year, (int)$request->term,
            (bool)$request->activate_now,
            $request->effective_date
        );

        return redirect()->route('finance.invoices.index')
            ->with('success', "$count items posted.");
    }
}
