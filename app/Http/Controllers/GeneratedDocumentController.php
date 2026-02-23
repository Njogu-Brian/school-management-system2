<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GeneratedDocumentController extends Controller
{
    /**
     * Display a listing of generated documents
     */
    public function index(Request $request)
    {
        $query = GeneratedDocument::with(['template', 'student', 'staff', 'generator']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $documents = $query->latest('generated_at')->paginate(20)->withQueryString();

        return view('documents.generated.index', compact('documents'));
    }

    /**
     * Display the specified generated document
     */
    public function show(GeneratedDocument $generatedDocument)
    {
        $generatedDocument->load(['template', 'student', 'staff', 'generator']);

        return view('documents.generated.show', compact('generatedDocument'));
    }

    /**
     * Download the generated document PDF
     */
    public function download(GeneratedDocument $generatedDocument)
    {
        if (!$generatedDocument->hasPdf()) {
            return back()->with('error', 'PDF file not found.');
        }

        $path = storage_path('app/public/' . $generatedDocument->pdf_path);
        $filename = $generatedDocument->filename ?? 'document.pdf';

        return response()->download($path, $filename);
    }

    /**
     * Delete the generated document
     */
    public function destroy(GeneratedDocument $generatedDocument)
    {
        // Delete PDF file if exists
        if ($generatedDocument->pdf_path && storage_public()->exists($generatedDocument->pdf_path)) {
            storage_public()->delete($generatedDocument->pdf_path);
        }

        $generatedDocument->delete();

        return redirect()
            ->route('generated-documents.index')
            ->with('success', 'Document deleted successfully.');
    }
}

