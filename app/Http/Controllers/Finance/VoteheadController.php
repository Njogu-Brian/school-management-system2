<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Votehead;
use App\Services\VoteheadImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;

class VoteheadController extends Controller
{
    public function index()
    {
        $voteheads = Votehead::all();
        return view('finance.voteheads.index', compact('voteheads'));
    }

    public function create()
    {
        return view('finance.voteheads.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:voteheads,name',
            'description' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'charge_type' => 'required|in:per_student,once,once_annually,per_family',
            'preferred_term' => 'nullable|in:1,2,3',
        ]);

        Votehead::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'charge_type' => $request->charge_type,
            'preferred_term' => $request->charge_type === 'once_annually' ? $request->preferred_term : null,
        ]);

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead created successfully.');
    }

    public function update(Request $request, Votehead $votehead)
    {
        $request->validate([
            'name' => 'required|unique:voteheads,name,' . $votehead->id,
            'description' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'charge_type' => 'required|in:per_student,once,once_annually,per_family',
            'preferred_term' => 'nullable|in:1,2,3',
        ]);

        $votehead->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'charge_type' => $request->charge_type,
            'preferred_term' => $request->charge_type === 'once_annually' ? $request->preferred_term : null,
        ]);

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead updated successfully.');
    }

        
    public function edit(Votehead $votehead)
    {
        return view('finance.voteheads.edit', compact('votehead'));
    }

    public function destroy(Votehead $votehead)
    {
        $votehead->delete();
        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead deleted successfully.');
    }

    /**
     * Show import form
     */
    public function import()
    {
        $categories = \App\Models\VoteheadCategory::active()->orderBy('display_order')->get();
        return view('finance.voteheads.import', compact('categories'));
    }

    /**
     * Process import
     */
    public function processImport(Request $request, VoteheadImportService $importService)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            // Parse file (Excel or CSV)
            $file = $request->file('csv_file');
            $extension = $file->getClientOriginalExtension();
            
            if (in_array(strtolower($extension), ['xlsx', 'xls'])) {
                // Parse Excel file
                $rows = $this->parseExcelFile($file);
            } else {
                // Parse CSV file
                $rows = $this->parseCsvFile($file);
            }
            
            if (empty($rows)) {
                return back()->with('error', 'File is empty or could not be parsed.');
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

            return redirect()->route('finance.voteheads.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template with dropdowns
     */
    public function downloadTemplate(VoteheadImportService $importService)
    {
        return $importService->generateExcelTemplate();
    }

    /**
     * Parse Excel file into array
     */
    protected function parseExcelFile($file): array
    {
        $data = Excel::toArray([], $file);
        
        if (empty($data) || empty($data[0])) {
            return [];
        }
        
        $sheet = $data[0];
        if (count($sheet) < 2) {
            return [];
        }
        
        // First row is headers
        $headerRow = $sheet[0];
        $headers = array_map(function($header) {
            return strtolower(trim(str_replace(' ', '_', $header ?? '')));
        }, $headerRow);
        
        $rows = [];
        // Process data rows (skip header row)
        for ($i = 1; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            $rowData = [];
            
            foreach ($headers as $index => $header) {
                $rowData[$header] = $row[$index] ?? null;
            }
            
            // Skip completely empty rows
            if (array_filter($rowData, function($val) { return !empty($val); })) {
                $rows[] = $rowData;
            }
        }
        
        return $rows;
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
