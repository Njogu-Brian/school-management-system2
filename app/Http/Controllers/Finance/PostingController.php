<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\PostingService; // Keep for backward compatibility
use App\Services\FeePostingService;
use App\Models\FeePostingRun;
use Illuminate\Http\Request;

class PostingController extends Controller
{
    protected FeePostingService $postingService;

    public function __construct(FeePostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    public function index(Request $request)
    {
        // filters form
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        $streams    = \App\Models\Academics\Stream::orderBy('name')->get();
        $voteheads  = \App\Models\Votehead::orderBy('name')->get();
        
        // Get posting run history
        $runs = FeePostingRun::with(['academicYear', 'term', 'postedBy'])
            ->orderBy('posted_at', 'desc')
            ->paginate(20);

        return view('finance.posting.index', compact('classrooms','streams','voteheads','runs'));
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
        
        // Use enhanced service with diffs
        $result = $this->postingService->previewWithDiffs($request->all());
        
        return view('finance.posting.preview', [
            'diffs' => $result['diffs'],
            'summary' => $result['summary'],
            'filters' => $request->all()
        ]);
    }

    public function commit(Request $request)
    {
        $request->validate([
            'year'=>'required|integer', 'term'=>'required|in:1,2,3',
            'activate_now'=>'required|boolean',
            'effective_date'=>'nullable|date',
            'diffs'=>'required|array', // array of diffs from preview
        ]);

        $diffs = collect($request->diffs);
        $run = $this->postingService->commitWithTracking(
            $diffs,
            (int)$request->year,
            (int)$request->term,
            (bool)$request->activate_now,
            $request->effective_date,
            $request->only(['votehead_id', 'class_id', 'stream_id', 'student_id'])
        );

        return redirect()
            ->route('finance.posting.show', $run)
            ->with('success', "Posting run #{$run->id} completed. {$run->items_posted_count} items posted.");
    }
    
    public function show(FeePostingRun $run)
    {
        $run->load(['academicYear', 'term', 'postedBy', 'diffs.student', 'diffs.votehead']);
        return view('finance.posting.show', compact('run'));
    }
    
    public function reverse(FeePostingRun $run)
    {
        try {
            $this->postingService->reversePostingRun($run);
            return redirect()
                ->route('finance.posting.index')
                ->with('success', "Posting run #{$run->id} reversed successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
