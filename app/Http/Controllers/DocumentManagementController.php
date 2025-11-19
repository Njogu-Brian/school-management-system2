<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with(['documentable', 'uploader']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('documentable_type')) {
            $query->where('documentable_type', $request->documentable_type);
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create(Request $request)
    {
        $documentableType = $request->get('type'); // student, staff, etc.
        $documentableId = $request->get('id');

        $students = Student::orderBy('first_name')->get();
        $staff = Staff::orderBy('first_name')->get();

        return view('documents.create', compact('documentableType', 'documentableId', 'students', 'staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'required|in:student,staff,academic,financial,administrative,other',
            'document_type' => 'required|in:report,certificate,letter,form,policy,other',
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $document = Document::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => $validated['category'],
            'document_type' => $validated['document_type'],
            'documentable_type' => $validated['documentable_type'],
            'documentable_id' => $validated['documentable_id'],
            'version' => 1,
            'is_active' => true,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('documents.show', $document)
            ->with('success', 'Document uploaded successfully.');
    }

    public function show(Document $document)
    {
        $document->load(['documentable', 'uploader', 'versions']);
        return view('documents.show', compact('document'));
    }

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function updateVersion(Request $request, Document $document)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        $maxVersion = $document->versions()->max('version') ?? $document->version;
        $newVersion = $maxVersion + 1;

        Document::create([
            'title' => $document->title,
            'description' => $document->description,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => $document->category,
            'document_type' => $document->document_type,
            'documentable_type' => $document->documentable_type,
            'documentable_id' => $document->documentable_id,
            'version' => $newVersion,
            'parent_document_id' => $document->id,
            'is_active' => true,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'New version uploaded successfully.');
    }

    public function destroy(Document $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }
}
