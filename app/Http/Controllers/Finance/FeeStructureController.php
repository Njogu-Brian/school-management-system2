<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeCharge;
use App\Services\FeeStructureImportService;
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
        $voteheads = \App\Models\Votehead::all();

        $selectedClassroom = $request->query('classroom_id');
        $selectedCategory = $request->query('student_category_id') ?? $categories->first()?->id;

        $feeStructure = null;
        $charges = [];

        if ($selectedClassroom && $selectedCategory) {
            $feeStructure = FeeStructure::with('charges')
                ->where('classroom_id', $selectedClassroom)
                ->where('student_category_id', $selectedCategory)
                ->first();

            if ($feeStructure) {
                $charges = $feeStructure->charges;
            }
        }

        return view('finance.fee_structures.manage', compact('classrooms', 'voteheads', 'selectedClassroom', 'selectedCategory', 'feeStructure', 'charges', 'categories'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'student_category_id' => 'required|exists:student_categories,id',
            'year' => 'required|numeric',
            'charges' => 'required|array',
            'charges.*.votehead_id' => 'required|exists:voteheads,id',
            'charges.*.term_1' => 'nullable|numeric|min:0',
            'charges.*.term_2' => 'nullable|numeric|min:0',
            'charges.*.term_3' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            $structure = FeeStructure::updateOrCreate(
                [
                    'classroom_id' => $validated['classroom_id'],
                    'student_category_id' => $validated['student_category_id'],
                ],
                ['year' => $validated['year']]
            );

            // Remove old charges
            $structure->charges()->delete();

            foreach ($validated['charges'] as $charge) {
                foreach ([1 => $charge['term_1'], 2 => $charge['term_2'], 3 => $charge['term_3']] as $term => $amount) {
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
        ])
                        ->with('success', 'Fee structure saved successfully.');
    }
   
    public function replicateTo(Request $request)
    {
        $request->validate([
            'source_structure_id' => 'nullable|exists:fee_structures,id',
            'source_classroom_id' => 'nullable|exists:classrooms,id',
            'target_classroom_ids' => 'required|array|min:1',
            'target_classroom_ids.*' => 'exists:classrooms,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id' => 'nullable|exists:terms,id',
            'student_category_id' => 'nullable|exists:student_categories,id',
        ]);

        // Get source structure
        if ($request->source_structure_id) {
            $source = FeeStructure::with('charges')->findOrFail($request->source_structure_id);
        } elseif ($request->source_classroom_id) {
            $source = FeeStructure::with('charges')
                ->where('classroom_id', $request->source_classroom_id)
                ->where('is_active', true)
                ->first();
        } else {
            return back()->with('error', 'Please specify either source structure or source classroom.');
        }

        if (!$source) {
            return back()->with('error', 'Source fee structure not found.');
        }

        $replicated = $source->replicateTo(
            $request->target_classroom_ids,
            $request->academic_year_id,
            $request->term_id,
            $request->student_category_id
        );

        return back()->with('success', "Fee structure replicated to " . count($replicated) . " classroom(s).");
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
        $voteheads = \App\Models\Votehead::all();

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
