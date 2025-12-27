<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\LegacyFinanceImportBatch;
use App\Models\LegacyStatementLine;
use App\Services\LegacyFinanceImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LegacyFinanceImportController extends Controller
{
    public function index(Request $request)
    {
        $batches = LegacyFinanceImportBatch::latest()->paginate(15);

        return view('finance.legacy-imports.index', [
            'batches' => $batches,
        ]);
    }

    public function show(LegacyFinanceImportBatch $batch)
    {
        $batch->load(['terms' => function ($query) {
            $query->orderBy('student_name');
        }]);

        $lines = LegacyStatementLine::where('batch_id', $batch->id)
            ->with('term')
            ->orderBy('term_id')
            ->orderBy('sequence_no')
            ->paginate(50);

        return view('finance.legacy-imports.show', [
            'batch' => $batch,
            'lines' => $lines,
        ]);
    }

    public function store(Request $request, LegacyFinanceImportService $service)
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf',
            'class_label' => 'nullable|string|max:50',
        ]);

        $path = $request->file('pdf')->store('legacy-imports');
        $fullPath = Storage::path($path);

        $result = $service->import(
            $fullPath,
            $validated['class_label'] ?? null,
            $request->user()?->id
        );

        return redirect()
            ->route('finance.legacy-imports.show', $result['batch_id'])
            ->with('success', 'Legacy statements imported. Review draft lines and confirm.');
    }
}

