<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\PostingService; // Keep for backward compatibility
use App\Services\FeePostingService;
use App\Models\FeePostingRun;
use App\Models\StudentCategory;
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
        $categories = StudentCategory::orderBy('name')->get();
        
        // Get posting run history
        $runs = FeePostingRun::with(['academicYear', 'term', 'postedBy'])
            ->orderBy('posted_at', 'desc')
            ->paginate(20);

        return view('finance.posting.index', compact('classrooms','streams','voteheads','categories','runs'));
    }

    public function preview(Request $request)
    {
        // For POST requests (initial form submission), require year and term
        if ($request->isMethod('post')) {
            $request->validate([
                'year'=>'required|integer',
                'term'=>'required|in:1,2,3',
                'votehead_id'=>'nullable|exists:voteheads,id',
                'class_id'=>'nullable|exists:classrooms,id',
                'stream_id'=>'nullable|exists:streams,id',
                'student_id'=>'nullable|exists:students,id',
                'student_ids'=>'nullable|array',
                'student_ids.*'=>'exists:students,id',
                'student_category_id'=>'nullable|exists:student_categories,id',
                'effective_date'=>'nullable|date'
            ]);

            // POST-Redirect-GET: redirect to same URL with query params so refresh/pagination work
            $query = array_filter([
                'year' => $request->input('year'),
                'term' => $request->input('term'),
                'votehead_id' => $request->input('votehead_id'),
                'class_id' => $request->input('class_id'),
                'stream_id' => $request->input('stream_id'),
                'student_id' => $request->input('student_id'),
                'student_category_id' => $request->input('student_category_id'),
                'effective_date' => $request->input('effective_date'),
                'student_ids' => $request->input('student_ids'),
            ], fn($v) => $v !== null && $v !== '');

            // Flatten student_ids for query string (array becomes student_ids[]=1&student_ids[]=2)
            if (isset($query['student_ids']) && is_array($query['student_ids'])) {
                $ids = $query['student_ids'];
                unset($query['student_ids']);
                return redirect()->route('finance.posting.preview', $query)
                    ->with('posting_preview_student_ids', $ids);
            }

            return redirect()->route('finance.posting.preview', $query);
        }

        // For GET requests (pagination, refresh): require year and term in URL
        $request->validate([
            'year'=>'nullable|integer',
            'term'=>'nullable|in:1,2,3',
            'votehead_id'=>'nullable|exists:voteheads,id',
            'class_id'=>'nullable|exists:classrooms,id',
            'stream_id'=>'nullable|exists:streams,id',
            'student_id'=>'nullable|exists:students,id',
            'student_ids'=>'nullable|array',
            'student_ids.*'=>'exists:students,id',
            'student_category_id'=>'nullable|exists:student_categories,id',
            'effective_date'=>'nullable|date'
        ]);

        if (!$request->filled('year') || !$request->filled('term')) {
            return redirect()->route('finance.posting.index')
                ->with('error', 'Please select year and term to preview.');
        }

        // Restore student_ids from session if we redirected with them (arrays don't go in query string well)
        $studentIds = $request->input('student_ids');
        if (empty($studentIds) && session()->has('posting_preview_student_ids')) {
            $studentIds = session()->pull('posting_preview_student_ids', []);
            $request->merge(['student_ids' => $studentIds]);
        }

        // Use enhanced service with diffs
        $result = $this->postingService->previewWithDiffs($request->all());
        
        // Add stable preview index for reject selections
        $diffs = $result['diffs']->values()->map(function ($diff, $index) {
            $diff['_preview_index'] = $index;
            return $diff;
        });
        $groupedByStudent = $diffs->groupBy('student_id');
        
        // Paginate students (not individual diffs) for better UX
        $perPage = (int)$request->get('per_page', 25); // Default 25, options: 25, 50, 100, 200
        $currentPage = $request->get('page', 1);
        $total = $groupedByStudent->count();
        $offset = ($currentPage - 1) * $perPage;
        
        // Get paginated student groups
        $paginatedStudents = $groupedByStudent->slice($offset, $perPage);
        
        // Prepare query parameters for pagination links (include all filter params)
        $queryParams = array_filter([
            'year' => $request->input('year'),
            'term' => $request->input('term'),
            'votehead_id' => $request->input('votehead_id'),
            'class_id' => $request->input('class_id'),
            'stream_id' => $request->input('stream_id'),
            'student_id' => $request->input('student_id'),
            'student_category_id' => $request->input('student_category_id'),
            'effective_date' => $request->input('effective_date'),
            'per_page' => $perPage,
        ], fn($value) => $value !== null && $value !== '');
        
        // Create paginator for students
        $studentsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedStudents,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $queryParams,
                'pageName' => 'page'
            ]
        );
        
        return view('finance.posting.preview', [
            'groupedDiffs' => $studentsPaginator, // Paginated student groups
            'allDiffs' => $diffs, // Keep all diffs for form submission
            'summary' => $result['summary'],
            'filters' => $request->all(),
            'perPage' => $perPage
        ]);
    }

    public function commit(Request $request)
    {
        $request->validate([
            'year'=>'required|integer', 'term'=>'required|in:1,2,3',
            'activate_now'=>'required|boolean',
            'effective_date'=>'nullable|date',
            'diffs_json'=>'required|string', // JSON-encoded diffs to avoid max_input_vars limit
            'rejected' => 'nullable|array',
            'rejected.*' => 'integer',
        ]);

        // Decode JSON-encoded diffs (base64 encoded to avoid issues with special characters)
        $diffsArray = json_decode(base64_decode($request->diffs_json), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($diffsArray)) {
            return back()->with('error', 'Invalid diffs data. Please try the preview again.');
        }
        
        $diffs = collect($diffsArray);
        $rejected = collect($request->input('rejected', []))->map(fn($v) => (int) $v)->unique()->values();
        
        if ($rejected->isNotEmpty()) {
            $this->postingService->rejectPendingDiffs($diffs, (int) $request->year, (int) $request->term, $rejected->all());
            $diffs = $diffs->reject(function ($diff) use ($rejected) {
                return isset($diff['_preview_index']) && $rejected->contains((int) $diff['_preview_index']);
            })->values();
        }
        
        if ($diffs->isEmpty()) {
            return redirect()
                ->route('finance.posting.index')
                ->with('info', 'All changes were rejected. No fees were posted.');
        }
        
        try {
            $run = $this->postingService->commitWithTracking(
                $diffs,
                (int)$request->year,
                (int)$request->term,
                (bool)$request->activate_now,
                $request->effective_date,
                $request->only(['votehead_id', 'class_id', 'stream_id', 'student_id', 'student_ids', 'student_category_id'])
            );

            return redirect()
                ->route('finance.posting.show', $run)
                ->with('success', "Posting run #{$run->id} completed. {$run->items_posted_count} items posted.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
    
    public function show(FeePostingRun $run)
    {
        $run->load([
            'academicYear', 'term', 'postedBy',
            'diffs.student', 'diffs.votehead',
            'invoiceItems.invoice.student', 'invoiceItems.votehead',
        ]);
        return view('finance.posting.show', compact('run'));
    }
    
    public function reverse(FeePostingRun $run)
    {
        try {
            $this->postingService->reversePostingRun($run);
            
            // Read affected payment count from run notes if available
            $message = "Posting run #{$run->id} reversed successfully.";
            if ($run->notes && str_contains($run->notes, 'payment(s)')) {
                // Extract payment count from notes
                if (preg_match('/(\d+)\s+payment\(s\)/', $run->notes, $matches)) {
                    $message .= " {$matches[1]} payment(s) have been freed and can be carried forward to other invoices.";
                }
            }
            
            return redirect()
                ->route('finance.posting.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function reverseStudent(FeePostingRun $run, Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);
        
        try {
            $student = \App\Models\Student::findOrFail($request->student_id);
            $this->postingService->reverseStudentPosting($run, $student->id);
            
            $message = "Posting reversed for student {$student->first_name} {$student->last_name} ({$student->admission_number}) successfully.";
            
            return redirect()
                ->route('finance.posting.show', $run)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
