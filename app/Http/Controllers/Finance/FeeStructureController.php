<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeCharge;
use App\Services\FeeStructureImportService;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class FeeStructureController extends Controller
{
    public function index()
    {
        $structures = FeeStructure::with('classroom', 'charges.votehead')->get();
        $classrooms = \App\Models\Academics\Classroom::all();
        return view('finance.fee_structures.index', compact('structures', 'classrooms'));
    }
    public function show(FeeStructure $feeStructure)
    {
        $feeStructure->load('classroom', 'charges.votehead');

        return view('finance.fee_structures.show', compact('feeStructure'));
    }
    public function manage(Request $request)
    {
        $classrooms = \App\Models\Academics\Classroom::all();
        $categories = \App\Models\StudentCategory::orderBy('name')->get();
        $transportVoteheadId = TransportFeeService::transportVotehead()->id;
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardVoteheadId = $balanceBroughtForwardVotehead ? $balanceBroughtForwardVotehead->id : null;
        
        $voteheads = \App\Models\Votehead::where('id', '!=', $transportVoteheadId)
            ->when($balanceBroughtForwardVoteheadId, function($q) use ($balanceBroughtForwardVoteheadId) {
                return $q->where('id', '!=', $balanceBroughtForwardVoteheadId);
            })
            ->get();
        $academicYears = \App\Models\AcademicYear::orderByDesc('year')->get();

        $selectedClassroom = $request->query('classroom_id');
        $selectedCategory = $request->query('student_category_id') ?? $categories->first()?->id;
        $selectedAcademicYearId = $request->query('academic_year_id') ?? $academicYears->firstWhere('is_active', true)?->id ?? $academicYears->first()?->id;
        $selectedAcademicYear = $academicYears->firstWhere('id', $selectedAcademicYearId);

        $feeStructure = null;
        $charges = [];

        if ($selectedClassroom && $selectedCategory && $selectedAcademicYearId) {
            $feeStructure = FeeStructure::with(['charges', 'studentCategory'])
                ->where('classroom_id', $selectedClassroom)
                ->where('student_category_id', $selectedCategory)
                ->where(function ($q) use ($selectedAcademicYearId, $selectedAcademicYear) {
                    $q->where('academic_year_id', $selectedAcademicYearId);
                    if ($selectedAcademicYear) {
                        $q->orWhere('year', $selectedAcademicYear->year);
                    }
                })
                ->first();

            if ($feeStructure) {
                $charges = $feeStructure->charges;
            }
        }

        return view('finance.fee_structures.manage', compact(
            'classrooms',
            'voteheads',
            'selectedClassroom',
            'selectedCategory',
            'selectedAcademicYearId',
            'selectedAcademicYear',
            'feeStructure',
            'charges',
            'categories',
            'academicYears'
        , 'transportVoteheadId'));
    }

    public function save(Request $request)
    {
        try {
            // Normalize empty strings to null for validation
            $charges = $request->input('charges', []);
            foreach ($charges as $key => $charge) {
                foreach (['term_1', 'term_2', 'term_3'] as $term) {
                    if (isset($charge[$term]) && $charge[$term] === '') {
                        $charges[$key][$term] = null;
                    }
                }
            }
            $request->merge(['charges' => $charges]);
            
            $validated = $request->validate([
                'classroom_id' => 'required|exists:classrooms,id',
                'student_category_id' => 'required|exists:student_categories,id',
                'academic_year_id' => 'required|exists:academic_years,id',
                'year' => 'required|digits:4|exists:academic_years,year',
                'charges' => 'required|array|min:1',
                'charges.*.votehead_id' => 'required|exists:voteheads,id',
                'charges.*.term_1' => 'nullable|numeric|min:0',
                'charges.*.term_2' => 'nullable|numeric|min:0',
                'charges.*.term_3' => 'nullable|numeric|min:0',
            ]);

            $transportVoteheadId = TransportFeeService::transportVotehead()->id;
            $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
            $balanceBroughtForwardVoteheadId = $balanceBroughtForwardVotehead ? $balanceBroughtForwardVotehead->id : null;

            DB::transaction(function () use ($validated, $transportVoteheadId, $balanceBroughtForwardVoteheadId) {
                $yearValue = $validated['year'];

                $structure = FeeStructure::updateOrCreate(
                    [
                        'classroom_id' => $validated['classroom_id'],
                        'student_category_id' => $validated['student_category_id'],
                        'academic_year_id' => $validated['academic_year_id'],
                    ],
                    [
                        'year' => $yearValue,
                        'academic_year_id' => $validated['academic_year_id'],
                    ]
                );

                // Remove old charges
                $structure->charges()->delete();

                foreach ($validated['charges'] as $charge) {
                    if (!isset($charge['votehead_id']) 
                        || (int) $charge['votehead_id'] === $transportVoteheadId
                        || ($balanceBroughtForwardVoteheadId && (int) $charge['votehead_id'] === $balanceBroughtForwardVoteheadId)) {
                        // Transport and balance brought forward are managed separately from fee structures
                        continue;
                    }
                    
                    foreach ([1 => $charge['term_1'] ?? null, 2 => $charge['term_2'] ?? null, 3 => $charge['term_3'] ?? null] as $term => $amount) {
                        // Convert to float and handle null/empty values
                        $amount = $amount === null || $amount === '' ? 0 : (float) $amount;
                        if ($amount > 0) {
                            FeeCharge::create([
                                'fee_structure_id' => $structure->id,
                                'votehead_id' => $charge['votehead_id'],
                                'term' => $term,
                                'amount' => $amount,
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('finance.fee-structures.manage', [
                'classroom_id' => $request->classroom_id,
                'student_category_id' => $request->student_category_id,
                'academic_year_id' => $request->academic_year_id,
            ])
                            ->with('success', 'Fee structure saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('finance.fee-structures.manage', [
                'classroom_id' => $request->classroom_id,
                'student_category_id' => $request->student_category_id,
                'academic_year_id' => $request->academic_year_id,
            ])
                            ->withErrors($e->errors())
                            ->withInput();
        } catch (\Exception $e) {
            \Log::error('Fee structure save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);
            
            return redirect()->route('finance.fee-structures.manage', [
                'classroom_id' => $request->classroom_id,
                'student_category_id' => $request->student_category_id,
                'academic_year_id' => $request->academic_year_id,
            ])
                            ->with('error', 'Failed to save fee structure: ' . $e->getMessage());
        }
    }
   
    public function replicateTo(Request $request)
    {
        $request->validate([
            'source_structure_id' => 'nullable|exists:fee_structures,id',
            'source_classroom_id' => 'nullable|exists:classrooms,id',
            'source_category_id' => 'nullable|exists:student_categories,id',
            'target_classroom_ids' => 'required|array|min:1',
            'target_classroom_ids.*' => 'exists:classrooms,id',
            'target_category_ids' => 'required|array|min:1',
            'target_category_ids.*' => 'exists:student_categories,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
        ]);

        // Get source structure - CRITICAL: Must match the exact source category
        if ($request->source_structure_id) {
            $source = FeeStructure::with('charges')->findOrFail($request->source_structure_id);
        } elseif ($request->source_classroom_id && $request->source_category_id) {
            // CRITICAL: Filter by both classroom AND category to get the correct source structure
            $sourceQuery = FeeStructure::with('charges')
                ->where('classroom_id', $request->source_classroom_id)
                ->where('is_active', true);
            
            // Match the exact source category
            if ($request->source_category_id) {
                $sourceQuery->where('student_category_id', (int)$request->source_category_id);
            } else {
                $sourceQuery->whereNull('student_category_id');
            }
            
            // Also match academic year if provided
            if ($request->academic_year_id) {
                $sourceQuery->where(function($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                    // Also check year column for backward compatibility
                    $academicYear = \App\Models\AcademicYear::find($request->academic_year_id);
                    if ($academicYear) {
                        $q->orWhere('year', $academicYear->year);
                    }
                });
            }
            
            $source = $sourceQuery->latest()->first();
        } else {
            return back()->with('error', 'Please specify either source structure or both source classroom and category.');
        }

        if (!$source) {
            return back()->with('error', 'Source fee structure not found for the specified classroom and category.');
        }

        // Verify source structure matches the expected category
        if ($request->source_category_id && $source->student_category_id != (int)$request->source_category_id) {
            \Log::warning('Fee structure replication: Source structure category mismatch', [
                'expected_category_id' => $request->source_category_id,
                'found_category_id' => $source->student_category_id,
                'source_structure_id' => $source->id,
            ]);
            return back()->with('error', 'Source fee structure category does not match. Please ensure you are viewing the correct category.');
        }

        if ($source->charges->isEmpty()) {
            return back()->with('error', 'Source fee structure has no charges to replicate.');
        }
        
        // Log the source structure being used for replication
        \Log::info('Fee structure replication: Using source structure', [
            'source_structure_id' => $source->id,
            'source_classroom_id' => $source->classroom_id,
            'source_category_id' => $source->student_category_id,
            'source_category_name' => $source->studentCategory->name ?? 'General',
            'charges_count' => $source->charges->count(),
        ]);

        try {
            $totalReplicated = 0;
            $replicatedCategories = [];
            
            // Replicate to each selected category
            foreach ($request->target_category_ids as $targetCategoryId) {
                // Ensure category ID is cast to integer
                $targetCategoryId = (int)$targetCategoryId;
                
                $replicated = $source->replicateTo(
                    $request->target_classroom_ids,
                    $request->academic_year_id,
                    $request->term_id,
                    $targetCategoryId // Use the selected target category (explicitly cast to int)
                );
                
                $totalReplicated += count($replicated);
                
                // Track which categories were replicated
                $category = \App\Models\StudentCategory::find($targetCategoryId);
                if ($category) {
                    $replicatedCategories[] = $category->name;
                }
            }

            $categoryList = implode(', ', $replicatedCategories);
            $categoryCount = count($replicatedCategories);
            $classroomCount = count($request->target_classroom_ids);
            
            return back()->with('success', 
                "Fee structure replicated to {$classroomCount} classroom(s) across {$categoryCount} category/categories: {$categoryList}. " .
                "Total structures created: {$totalReplicated}."
            );
        } catch (\Exception $e) {
            \Log::error('Fee structure replication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to replicate fee structure: ' . $e->getMessage());
        }
    }

    /**
     * Replicate fee structure to other terms within the same class and category
     */
    public function replicateTerms(Request $request)
    {
        $request->validate([
            'source_structure_id' => 'nullable|exists:fee_structures,id',
            'source_classroom_id' => 'nullable|exists:classrooms,id',
            'source_category_id' => 'required|exists:student_categories,id',
            'target_terms' => 'required|array|min:1',
            'target_terms.*' => 'in:1,2,3',
            'academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        // Get source structure
        if ($request->source_structure_id) {
            $source = FeeStructure::with('charges')->findOrFail($request->source_structure_id);
        } elseif ($request->source_classroom_id) {
            $source = FeeStructure::with('charges')
                ->where('classroom_id', $request->source_classroom_id)
                ->where('student_category_id', $request->source_category_id)
                ->where('is_active', true)
                ->latest()
                ->first();
        } else {
            return back()->with('error', 'Please specify either source structure or source classroom.');
        }

        if (!$source) {
            return back()->with('error', 'Source fee structure not found.');
        }

        if ($source->charges->isEmpty()) {
            return back()->with('error', 'Source fee structure has no charges to replicate.');
        }

        // Verify source structure matches the specified category
        if ($source->student_category_id != $request->source_category_id) {
            return back()->with('error', 'Source structure category does not match specified category.');
        }

        try {
            $totalReplicated = 0;
            $replicatedTerms = [];
            
            // Get term models for the target terms
            $academicYearId = $request->academic_year_id ?? $source->academic_year_id;
            $academicYear = \App\Models\AcademicYear::find($academicYearId);
            
            foreach ($request->target_terms as $termNumber) {
                // Find the term model
                $term = \App\Models\Term::whereHas('academicYear', function($q) use ($academicYearId) {
                    if ($academicYearId) {
                        $q->where('id', $academicYearId);
                    }
                })
                ->where('name', 'like', "%Term {$termNumber}%")
                ->first();
                
                $termId = $term ? $term->id : null;
                
                // Replicate to the same classroom and category, but different term
                $replicated = $source->replicateTo(
                    [$source->classroom_id], // Same classroom
                    $academicYearId,
                    $termId, // Different term
                    $source->student_category_id // Same category
                );
                
                $totalReplicated += count($replicated);
                $replicatedTerms[] = "Term {$termNumber}";
            }

            $termList = implode(', ', $replicatedTerms);
            $classroomName = $source->classroom->name ?? 'Selected Class';
            $categoryName = $source->studentCategory->name ?? 'General';
            
            return back()->with('success', 
                "Fee structure replicated to {$termList} for {$classroomName} - {$categoryName}. " .
                "Total structures created/updated: {$totalReplicated}."
            );
        } catch (\Exception $e) {
            \Log::error('Fee structure term replication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Failed to replicate fee structure to terms: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function import()
    {
        $classrooms = \App\Models\Academics\Classroom::all();
        $academicYears = \App\Models\AcademicYear::all();
        $terms = \App\Models\Term::all();
        $streams = \App\Models\Academics\Stream::all();
        $transportVoteheadId = TransportFeeService::transportVotehead()->id;
        $balanceBroughtForwardVotehead = \App\Models\Votehead::where('code', 'BAL_BF')->first();
        $balanceBroughtForwardVoteheadId = $balanceBroughtForwardVotehead ? $balanceBroughtForwardVotehead->id : null;
        
        $voteheads = \App\Models\Votehead::where('id', '!=', $transportVoteheadId)
            ->when($balanceBroughtForwardVoteheadId, function($q) use ($balanceBroughtForwardVoteheadId) {
                return $q->where('id', '!=', $balanceBroughtForwardVoteheadId);
            })
            ->get();

        return view('finance.fee_structures.import', compact('classrooms', 'academicYears', 'terms', 'streams', 'voteheads'));
    }

    /**
     * Process import
     */
    public function processImport(Request $request, FeeStructureImportService $importService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        try {
            // Parse CSV file
            $file = $request->file('csv_file');
            $rows = $this->parseCsvFile($file);
            
            if (empty($rows)) {
                return back()->with('error', 'CSV file is empty or could not be parsed.');
            }

            // Import data
            $result = $importService->import($rows);

            // Prepare response message
            $message = sprintf(
                'Import completed: %d created, %d updated, %d failed.',
                $result['success'],
                $result['updated'],
                $result['failed']
            );

            if ($result['failed'] > 0 && !empty($result['errors'])) {
                return back()
                    ->with('import_result', $result)
                    ->with('warning', $message . ' Please review errors below.');
            }

            return redirect()->route('finance.fee-structures.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate(FeeStructureImportService $importService)
    {
        $csv = $importService->generateTemplate();
        
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="fee_structures_import_template.csv"',
        ]);
    }

    /**
     * Parse CSV file into array
     */
    protected function parseCsvFile($file): array
    {
        $rows = [];
        $headers = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        if ($handle === false) {
            return [];
        }

        // Read headers
        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return [];
        }

        // Normalize header names (trim, lowercase, replace spaces with underscores)
        $headers = array_map(function($header) {
            return strtolower(trim(str_replace(' ', '_', $header)));
        }, $headerRow);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }

            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? null;
            }

            $rows[] = $rowData;
        }

        fclose($handle);
        return $rows;
    }
}
