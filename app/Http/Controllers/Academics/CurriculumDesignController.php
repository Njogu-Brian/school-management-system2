<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCurriculumDesignRequest;
use App\Http\Requests\UpdateCurriculumDesignRequest;
use App\Models\CurriculumDesign;
use App\Models\Academics\Subject;
use App\Jobs\ParseCurriculumDesignJob;
use App\Services\CurriculumParsingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CurriculumDesignController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:curriculum_designs.view|curriculum_designs.view_own')->only(['index', 'show']);
        $this->middleware('permission:curriculum_designs.create')->only(['create', 'store']);
        $this->middleware('permission:curriculum_designs.edit')->only(['edit', 'update', 'reprocess']);
        $this->middleware('permission:curriculum_designs.delete')->only(['destroy']);
    }

    /**
     * Display a listing of curriculum designs.
     */
    public function index(Request $request)
    {
        $query = CurriculumDesign::with(['subject', 'uploader'])
            ->withCount(['learningAreas', 'strands', 'embeddings']);
        
        $subjects = \App\Models\Academics\Subject::active()->orderBy('name')->get();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by subject
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        // Filter by class level
        if ($request->filled('class_level')) {
            $query->where('class_level', $request->class_level);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('class_level', 'like', "%{$search}%")
                  ->orWhereHas('subject', function($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Show only own uploads if user doesn't have view all permission
        if (!auth()->user()->hasPermissionTo('curriculum_designs.view')) {
            $query->where('uploaded_by', auth()->id());
        }

        $curriculumDesigns = $query->latest()->paginate(20);

        return view('academics.curriculum_designs.index', compact('curriculumDesigns', 'subjects'));
    }

    /**
     * Show the form for creating a new curriculum design.
     */
    public function create()
    {
        $subjects = \App\Models\Academics\Subject::active()->orderBy('name')->get();
        return view('academics.curriculum_designs.create', compact('subjects'));
    }

    /**
     * Store a newly uploaded curriculum design.
     */
    public function store(StoreCurriculumDesignRequest $request)
    {
        try {
            $file = $request->file('file');
            
            // Calculate file checksum for duplicate detection
            $checksum = hash_file('sha256', $file->getRealPath());

            // Check for duplicates
            $existing = CurriculumDesign::where('checksum', $checksum)->first();
            if ($existing) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['file' => 'This file has already been uploaded.']);
            }

            // Store file
            $filePath = $file->store('curriculum_designs', 'private');
            
            // Get page count (approximate)
            $pages = $this->getPdfPageCount(Storage::path($filePath));

            // Create curriculum design record
            $curriculumDesign = CurriculumDesign::create([
                'title' => $request->title,
                'subject_id' => $request->subject_id,
                'class_level' => $request->class_level,
                'uploaded_by' => auth()->id(),
                'file_path' => $filePath,
                'pages' => $pages,
                'status' => 'processing',
                'checksum' => $checksum,
            ]);

            // Dispatch parsing job
            ParseCurriculumDesignJob::dispatch($curriculumDesign->id);

            // Log audit
            $curriculumDesign->audits()->create([
                'user_id' => auth()->id(),
                'action' => 'upload',
                'notes' => 'Curriculum design uploaded',
            ]);

            return redirect()->route('academics.curriculum-designs.show', $curriculumDesign)
                ->with('success', 'Curriculum design uploaded successfully. Processing will begin shortly.');
        } catch (\Exception $e) {
            Log::error('Curriculum design upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to upload curriculum design: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified curriculum design.
     */
    public function show(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('view', $curriculumDesign);

        $curriculumDesign->load([
            'subject',
            'uploader',
            'pages' => function($query) {
                $query->orderBy('page_number');
            },
            'learningAreas.strands.substrands.competencies',
            'learningAreas.strands.substrands.suggestedExperiences',
            'learningAreas.strands.substrands.assessmentRubrics',
            'audits.user',
        ]);

        return view('academics.curriculum_designs.show', compact('curriculumDesign'));
    }

    /**
     * Show the review page for extracted data.
     */
    public function review(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('update', $curriculumDesign);

        if ($curriculumDesign->status !== 'processed') {
            return redirect()->route('academics.curriculum-designs.show', $curriculumDesign)
                ->with('error', 'Curriculum design must be processed before review.');
        }

        $curriculumDesign->load([
            'learningAreas.strands.substrands.competencies',
            'learningAreas.strands.substrands.suggestedExperiences',
            'learningAreas.strands.substrands.assessmentRubrics',
        ]);

        return view('academics.curriculum_designs.review', compact('curriculumDesign'));
    }

    /**
     * Show the form for editing the specified curriculum design.
     */
    public function edit(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('update', $curriculumDesign);
        
        $subjects = \App\Models\Academics\Subject::active()->orderBy('name')->get();
        return view('academics.curriculum_designs.edit', compact('curriculumDesign', 'subjects'));
    }

    /**
     * Update the specified curriculum design.
     */
    public function update(UpdateCurriculumDesignRequest $request, CurriculumDesign $curriculumDesign)
    {
        $curriculumDesign->update($request->validated());

        $curriculumDesign->audits()->create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'notes' => 'Curriculum design metadata updated',
            'changes' => $request->validated(),
        ]);

        return redirect()->route('curriculum-designs.show', $curriculumDesign)
            ->with('success', 'Curriculum design updated successfully.');
    }

    /**
     * Reprocess a curriculum design.
     */
    public function reprocess(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('reprocess', $curriculumDesign);

        // Clear existing extracted data (optional - you may want to keep it)
        // $curriculumDesign->learningAreas()->delete();
        // $curriculumDesign->strands()->delete();

        $curriculumDesign->update(['status' => 'processing']);

        ParseCurriculumDesignJob::dispatch($curriculumDesign->id);

        $curriculumDesign->audits()->create([
            'user_id' => auth()->id(),
            'action' => 'reprocess',
            'notes' => 'Curriculum design reprocessing initiated',
        ]);

        return redirect()->route('academics.curriculum-designs.show', $curriculumDesign)
            ->with('success', 'Curriculum design reprocessing started.');
    }

    /**
     * Remove the specified curriculum design.
     */
    public function destroy(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('delete', $curriculumDesign);

        // Delete file
        if (Storage::disk('private')->exists($curriculumDesign->file_path)) {
            Storage::disk('private')->delete($curriculumDesign->file_path);
        }

        $curriculumDesign->delete();

        return redirect()->route('academics.curriculum-designs.index')
            ->with('success', 'Curriculum design deleted successfully.');
    }

    /**
     * Get parsing progress for a curriculum design
     */
    public function progress(CurriculumDesign $curriculumDesign)
    {
        $this->authorize('view', $curriculumDesign);

        $progress = CurriculumParsingService::getProgress($curriculumDesign->id);

        if (!$progress) {
            // If no progress data, check the status
            $curriculumDesign->refresh();
            return response()->json([
                'percentage' => $curriculumDesign->status === 'processed' ? 100 : 0,
                'pages_processed' => $curriculumDesign->pages ?? 0,
                'message' => ucfirst($curriculumDesign->status),
                'failed' => $curriculumDesign->status === 'failed',
                'status' => $curriculumDesign->status,
            ]);
        }

        $curriculumDesign->refresh();
        $progress['status'] = $curriculumDesign->status;
        $progress['total_pages'] = $curriculumDesign->pages ?? 0;

        return response()->json($progress);
    }

    /**
     * Get approximate page count of PDF
     */
    protected function getPdfPageCount(string $filePath): int
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return count($pdf->getPages());
        } catch (\Exception $e) {
            Log::warning('Failed to get PDF page count', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
