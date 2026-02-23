<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DocumentManagementController extends Controller
{
    protected array $defaultCategories = [
        'student', 'staff', 'academic', 'financial', 'administrative', 'other',
        'student_profile_photo', 'staff_profile_photo', 'parent_id_card', 'staff_id_card',
        'staff_resume', 'student_birth_certificate', 'driver_license', 'contract',
        'registration_certificate', 'license', 'policy',
    ];

    protected array $defaultTypes = [
        'report', 'certificate', 'letter', 'form', 'policy', 'id_card', 'photo',
        'resume', 'contract', 'license', 'registration', 'birth_certificate',
        'academic_record', 'financial_record', 'other',
    ];

    public function index(Request $request)
    {
        $query = Document::with(['documentable', 'uploader']);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('documentable_type')) {
            $query->where('documentable_type', $request->documentable_type);
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        $categories = $this->defaultCategories;
        $types = $this->defaultTypes;

        return view('documents.index', compact('documents', 'categories', 'types'));
    }

    public function create(Request $request)
    {
        $documentableType = $request->get('type'); // student, staff, etc.
        $documentableId = $request->get('id');

        $students = Student::orderBy('first_name')->get();
        $staff = Staff::orderBy('first_name')->get();

        $categories = $this->defaultCategories;
        $types = $this->defaultTypes;

        return view('documents.create', compact('documentableType', 'documentableId', 'students', 'staff', 'categories', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'required|string|max:100',
            'custom_category' => 'nullable|string|max:100',
            'document_type' => 'required|string|max:100',
            'custom_document_type' => 'nullable|string|max:100',
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
        ]);

        $category = $validated['category'] === 'custom' && $validated['custom_category']
            ? Str::slug($validated['custom_category'], '_')
            : $validated['category'];

        $documentType = $validated['document_type'] === 'custom' && $validated['custom_document_type']
            ? Str::slug($validated['custom_document_type'], '_')
            : $validated['document_type'];

        $file = $request->file('file');
        $path = $file->store('documents', config('filesystems.public_disk', 'public'));

        $document = Document::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'category' => $category,
            'document_type' => $documentType,
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
        $types = $this->defaultTypes;
        return view('documents.show', compact('document', 'types'));
    }

    public function download(Document $document)
    {
        if (!storage_public()->exists($document->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return storage_public()->download($document->file_path, $document->file_name);
    }

    public function preview(Document $document)
    {
        if (!storage_public()->exists($document->file_path)) {
            abort(404, 'File not found.');
        }
        $mime = $document->file_type ?? 'application/octet-stream';

        // For images and pdf, force inline view
        $inlineTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array(strtolower($mime), $inlineTypes)) {
            return storage_public()->response($document->file_path, $document->file_name, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            ]);
        }

        return storage_public()->download($document->file_path, $document->file_name);
    }

    public function email(Request $request, Document $document)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:150',
            'message' => 'nullable|string',
        ]);

        if (!storage_public()->exists($document->file_path)) {
            return back()->with('error', 'File not found.');
        }

        $content = storage_public()->get($document->file_path);
        $mime = $document->file_type ?? 'application/octet-stream';

        Mail::raw($validated['message'] ?? 'Please find the attached document.', function ($message) use ($validated, $document, $content, $mime) {
            $message->to($validated['to'])
                ->subject($validated['subject'])
                ->attachData($content, $document->file_name, ['mime' => $mime]);
        });

        return back()->with('success', 'Document emailed successfully.');
    }

    public function updateVersion(Request $request, Document $document)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'notes' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', config('filesystems.public_disk', 'public'));

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
        storage_public()->delete($document->file_path);
        $document->delete();
        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully.');
    }
}
