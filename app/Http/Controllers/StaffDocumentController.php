<?php

namespace App\Http\Controllers;

use App\Models\StaffDocument;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffDocumentController extends Controller
{
    public function index(Request $request)
    {
        $staffId = $request->get('staff_id');
        $documentType = $request->get('document_type');

        $query = StaffDocument::with(['staff', 'uploadedBy']);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        $documentTypes = $this->getDocumentTypes();

        return view('staff.documents.index', compact('documents', 'staff', 'documentTypes'));
    }

    public function create(Request $request)
    {
        $staffId = $request->get('staff_id');
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        $documentTypes = $this->getDocumentTypes();

        return view('staff.documents.create', compact('staff', 'documentTypes', 'staffId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'document_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240', // 10MB max
            'expiry_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $filePath = $file->store('staff_documents', 'public');

        StaffDocument::create([
            'staff_id' => $request->staff_id,
            'document_type' => $request->document_type,
            'title' => $request->title,
            'file_path' => $filePath,
            'expiry_date' => $request->expiry_date,
            'description' => $request->description,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('staff.documents.index')
            ->with('success', 'Document uploaded successfully.');
    }

    public function show(StaffDocument $document)
    {
        $document->load(['staff', 'uploadedBy']);
        return view('staff.documents.show', compact('document'));
    }

    public function destroy(StaffDocument $document)
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }

    public function download(StaffDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION));
    }

    private function getDocumentTypes()
    {
        return [
            'contract' => 'Employment Contract',
            'certificate' => 'Certificate',
            'id_copy' => 'ID Copy',
            'qualification' => 'Qualification',
            'other' => 'Other',
        ];
    }
}
